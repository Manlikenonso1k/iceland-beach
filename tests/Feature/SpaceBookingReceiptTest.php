<?php

namespace Tests\Feature;

use App\Filament\Resources\SpaceBookingResource;
use App\Http\Controllers\SpaceReceiptController;
use App\Models\ServiceBooking;
use App\Models\SpaceBooking;
use App\Models\User;
use App\Services\SpaceBookingReportService;
use App\Services\SpaceBookingWorkflowService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A MANUAL space booking took money off-system, so a receipt is the only record
 * that it happened and is required before the booking can be confirmed.
 * Website bookings are exempt — Paystack is already the receipt there.
 *
 * Receipts live on the PRIVATE disk and are reached through a permanent signed
 * route, because the links have to survive being embedded in an exported report
 * PDF and opened later with no panel session.
 */
class SpaceBookingReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake(SpaceReceiptController::DISK);
    }

    private function userWithRole(string $roleName): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($roleName, 'web');

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function space(float $price = 50000): ServiceBooking
    {
        return ServiceBooking::query()->create([
            'service_name' => 'Beach Cabana 1',
            'service_category' => 'Cabana',
            'service_price' => $price,
            'is_booked' => 'no',
        ]);
    }

    private function booking(string $source, array $overrides = []): SpaceBooking
    {
        return SpaceBooking::query()->create(array_merge([
            'space_id' => $this->space()->id,
            'space_name' => 'Beach Cabana 1',
            'guest_name' => 'Ada Guest',
            'guest_email' => 'ada@example.test',
            'guest_phone' => '08031234567',
            'guests' => 20,
            'check_in' => now()->addDays(3),
            'check_out' => now()->addDays(5),
            'status' => 'pending',
            'source' => $source,
        ], $overrides));
    }

    /** Puts a fake receipt on the private disk and links it to the booking. */
    private function attachReceipt(SpaceBooking $booking, string $name = 'receipt.pdf'): SpaceBooking
    {
        $path = 'space-receipts/' . str_repeat('a', 40) . '.pdf';
        Storage::disk(SpaceReceiptController::DISK)->put($path, '%PDF-1.4 fake');

        $booking->update([
            'receipt_path' => $path,
            'receipt_original_name' => $name,
            'receipt_mime' => 'application/pdf',
            'receipt_uploaded_at' => now(),
        ]);

        return $booking->fresh();
    }

    // -------------------------------------------------- the requirement itself

    public function test_a_manual_booking_cannot_be_confirmed_without_a_receipt(): void
    {
        $booking = $this->booking(SpaceBooking::SOURCE_MANUAL);

        $this->assertTrue($booking->requiresReceiptBeforeConfirming());

        try {
            app(SpaceBookingWorkflowService::class)->confirmBooking($booking);
            $this->fail('Confirming without a receipt should have been refused.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('receipt', $e->errors());
        }

        // Nothing moved: not the status, not the space hold, no email.
        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('no', $booking->space->fresh()->is_booked);
        Mail::assertNothingSent();
    }

    public function test_a_manual_booking_confirms_once_a_receipt_is_attached(): void
    {
        $booking = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL));

        app(SpaceBookingWorkflowService::class)->confirmBooking($booking);

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('booked', $booking->space->fresh()->is_booked);
        Mail::assertSent(\App\Mail\BookingConfirmedMail::class, 1);
    }

    public function test_a_website_booking_needs_no_receipt(): void
    {
        $booking = $this->booking(SpaceBooking::SOURCE_WEBSITE);

        $this->assertFalse($booking->requiresReceiptBeforeConfirming());

        app(SpaceBookingWorkflowService::class)->confirmBooking($booking);

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('Online', $booking->fresh()->receiptLabel());
    }

    public function test_a_paid_manual_booking_needs_no_receipt(): void
    {
        // Paystack has already recorded the money. Requiring a second document
        // would block the automatic confirmation that payment triggers.
        $booking = $this->booking(SpaceBooking::SOURCE_MANUAL, ['payment_status' => 'paid']);

        $this->assertFalse($booking->requiresReceiptBeforeConfirming());

        app(SpaceBookingWorkflowService::class)->confirmBooking($booking);

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_a_booking_created_in_the_backend_is_marked_manual(): void
    {
        $host = $this->userWithRole('Space Host');
        $this->actingAs($host);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $space = $this->space();

        \Livewire\Livewire::test(\App\Filament\Resources\SpaceBookingResource\Pages\CreateSpaceBooking::class)
            ->fillForm([
                'space_id' => $space->id,
                'space_name' => $space->service_name,
                'guest_name' => 'Counter Guest',
                'guest_phone' => '08039998888',
                'guests' => 15,
                'check_in' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'check_out' => now()->addDays(4)->format('Y-m-d H:i:s'),
                'status' => 'pending',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = SpaceBooking::query()->where('guest_phone', '08039998888')->firstOrFail();

        // This is what makes the receipt requirement apply.
        $this->assertSame(SpaceBooking::SOURCE_MANUAL, $booking->source);
        $this->assertTrue($booking->requiresReceiptBeforeConfirming());
    }

    public function test_a_guest_booking_from_the_website_is_marked_website(): void
    {
        $space = $this->space();

        $this->postJson('/api/service-bookings', [
            'service_id' => $space->id,
            'guest_name' => 'Web Guest',
            'guest_email' => 'web@example.test',
            'guests' => 10,
            'check_in' => now()->addDays(6)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
        ])->assertCreated();

        $booking = SpaceBooking::query()->firstOrFail();

        $this->assertSame(SpaceBooking::SOURCE_WEBSITE, $booking->source);
        $this->assertFalse($booking->requiresReceiptBeforeConfirming());
    }

    // -------------------------------------------------- the signed link

    public function test_a_signed_receipt_link_opens_without_logging_in(): void
    {
        $booking = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL));

        // No actingAs — this is the case that matters, a link opened from an
        // exported PDF on a device with no panel session.
        $this->get($booking->receiptUrl())
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_an_unsigned_or_tampered_link_is_rejected(): void
    {
        $booking = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL));

        // Unsigned: the bare route.
        $this->get('/space-receipts/' . $booking->id)->assertForbidden();

        // Tampered: a valid signature for a different id.
        $other = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL));
        $tampered = str_replace(
            '/space-receipts/' . $other->id . '?',
            '/space-receipts/' . $booking->id . '?',
            (string) $other->receiptUrl(),
        );

        $this->get($tampered)->assertForbidden();
    }

    public function test_the_link_has_no_expiry_so_an_archived_report_keeps_working(): void
    {
        // An exported report is read months later. A temporary link would 403
        // by then, which is worse than useless.
        $booking = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL));
        $url = (string) $booking->receiptUrl();

        $this->assertStringNotContainsString('expires=', $url);

        $this->travel(13)->months();

        $this->get($url)->assertOk();
    }

    public function test_a_missing_file_is_a_404_not_a_500(): void
    {
        $booking = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL));

        Storage::disk(SpaceReceiptController::DISK)->delete($booking->receipt_path);

        $this->get($booking->receiptUrl())->assertNotFound();
    }

    public function test_receipts_are_not_web_served_from_public(): void
    {
        $booking = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL));

        // Stored on the private disk, under an unguessable name, and never
        // reachable as a plain public URL.
        $this->assertStringStartsWith('space-receipts/', $booking->receipt_path);
        $this->assertStringNotContainsString('public', $booking->receipt_path);
        $this->assertSame('local', SpaceReceiptController::DISK);
    }

    // -------------------------------------------------- roles

    public function test_front_desk_can_open_a_receipt_but_not_change_it(): void
    {
        $booking = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL));
        $frontDesk = $this->userWithRole('Front Desk');

        // Viewing: allowed, both the list and the receipt itself.
        $this->actingAs($frontDesk)->get('/admin/space-bookings/list')->assertOk();
        $this->actingAs($frontDesk)->get($booking->receiptUrl())->assertOk();

        // Changing: refused at the server, by direct URL.
        $this->actingAs($frontDesk)
            ->get('/admin/space-bookings/' . $booking->id . '/edit')
            ->assertForbidden();

        $this->actingAs($frontDesk);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertFalse(SpaceBookingResource::canWrite());
    }

    public function test_a_space_host_may_upload(): void
    {
        $host = $this->userWithRole('Space Host');
        $booking = $this->booking(SpaceBooking::SOURCE_MANUAL);

        $this->actingAs($host)
            ->get('/admin/space-bookings/' . $booking->id . '/edit')
            ->assertOk();

        $this->actingAs($host);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertTrue(SpaceBookingResource::canWrite());
    }

    public function test_the_upload_input_offers_camera_or_files_on_a_phone(): void
    {
        // A mobile browser shows the Camera / Files chooser only when the
        // underlying input advertises both. Asserted on the rendered HTML
        // rather than in a browser, because this attribute IS the mechanism —
        // without it an iPhone offers Files only.
        $host = $this->userWithRole('Space Host');
        $booking = $this->booking(SpaceBooking::SOURCE_MANUAL);

        $html = $this->actingAs($host)
            ->get('/admin/space-bookings/' . $booking->id . '/edit')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('image/*,application/pdf', $html);
        $this->assertStringContainsString('capture', $html);
    }

    // -------------------------------------------------- reports

    public function test_report_rows_carry_the_receipt_label_and_a_signed_url(): void
    {
        $withReceipt = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL, [
            'check_in' => now()->setTime(10, 0),
            'check_out' => now()->addDay()->setTime(10, 0),
        ]));

        $this->booking(SpaceBooking::SOURCE_MANUAL, [
            'guest_name' => 'No Receipt Guest',
            'check_in' => now()->setTime(12, 0),
            'check_out' => now()->addDay()->setTime(12, 0),
        ]);

        $this->booking(SpaceBooking::SOURCE_WEBSITE, [
            'guest_name' => 'Web Guest',
            'check_in' => now()->setTime(14, 0),
            'check_out' => now()->addDay()->setTime(14, 0),
        ]);

        $service = app(SpaceBookingReportService::class);
        [$start, $end] = $service->range(['period' => 'daily', 'date' => now()->toDateString()]);

        $rows = $service->rows($start, $end);

        $this->assertCount(3, $rows);

        $labels = $rows->pluck('receipt')->all();

        $this->assertContains('View receipt', $labels);
        $this->assertContains('—', $labels);
        $this->assertContains('Online', $labels);

        $linked = $rows->firstWhere('receipt', 'View receipt');

        $this->assertNotNull($linked['receipt_url']);
        $this->assertStringContainsString('/space-receipts/' . $withReceipt->id, $linked['receipt_url']);
        $this->assertStringContainsString('signature=', $linked['receipt_url']);
    }

    public function test_the_exported_pdf_contains_a_working_receipt_link(): void
    {
        $booking = $this->attachReceipt($this->booking(SpaceBooking::SOURCE_MANUAL, [
            'check_in' => now()->setTime(10, 0),
            'check_out' => now()->addDay()->setTime(10, 0),
        ]));

        $response = app(SpaceBookingReportService::class)->download([
            'period' => 'daily',
            'date' => now()->toDateString(),
        ]);

        ob_start();
        $response->sendContent();
        $pdf = (string) ob_get_clean();

        $this->assertStringStartsWith('%PDF', $pdf);

        // The signed URL must survive into the PDF, otherwise the link in an
        // exported report is dead. DomPDF writes link targets as plain text in
        // the object stream.
        $this->assertStringContainsString('space-receipts', $pdf);

        // And that URL actually resolves, unauthenticated.
        $this->get($booking->receiptUrl())->assertOk();
    }

    public function test_the_room_report_is_unaffected_and_has_no_receipt_column(): void
    {
        // The receipt column is opt-in via showReceipts, so the room report
        // must render exactly as before.
        $response = app(\App\Services\BookingReportService::class)->download([
            'period' => 'daily',
            'date' => now()->toDateString(),
        ]);

        ob_start();
        $response->sendContent();
        $pdf = (string) ob_get_clean();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringNotContainsString('space-receipts', $pdf);
    }
}
