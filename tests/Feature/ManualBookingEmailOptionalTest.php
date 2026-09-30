<?php

namespace Tests\Feature;

use App\Filament\Resources\BookingResource\Pages\CreateBooking;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingDeclinedMail;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingWorkflowService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Staff take bookings over the counter and by telephone, where an email address
 * often does not exist. On the admin form the phone number is the identifier
 * and email is optional.
 *
 * The public form is deliberately not covered here — it still requires an
 * email, because Paystack needs a receipt address. See BookingRateLimitTest.
 */
class ManualBookingEmailOptionalTest extends TestCase
{
    use RefreshDatabase;

    private function room(): Room
    {
        return Room::query()->create([
            'room_name' => 'Premium Room 1',
            'room_category' => 'Premium',
            'room_price' => 90000,
            'is_booked' => 'no',
        ]);
    }

    private function staffUser(): User
    {
        // canAccessPanel() short-circuits on this address, so the test does not
        // depend on the spatie role tables being seeded.
        return User::query()->create([
            'name' => 'Front Desk',
            'email' => 'victorynonso9@gmail.com',
            'password' => 'password',
        ]);
    }

    private function bookingWithoutEmail(): Booking
    {
        return Booking::query()->create([
            'room_id' => $this->room()->id,
            'guest_name' => 'Walk-in Guest',
            'guest_email' => null,
            'guest_phone' => '08031234567',
            'room_type' => 'Premium Room 1',
            'check_in' => now()->addDay(),
            'check_out' => now()->addDays(3),
            'status' => 'pending',
        ]);
    }

    public function test_the_bookings_table_accepts_a_row_with_no_email(): void
    {
        // guest_email was created as a plain string() — NOT NULL. Without the
        // migration this fails at the database with an integrity violation
        // rather than surfacing as a form error.
        $booking = $this->bookingWithoutEmail();

        $this->assertNull($booking->fresh()->guest_email);
        $this->assertSame('08031234567', $booking->fresh()->guest_phone);
    }

    public function test_confirming_a_booking_with_no_email_holds_the_room_and_sends_nothing(): void
    {
        Mail::fake();

        $booking = $this->bookingWithoutEmail();

        app(BookingWorkflowService::class)->confirmBooking($booking);

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('booked', $booking->room->fresh()->is_booked);

        Mail::assertNothingSent();
    }

    public function test_rejecting_a_booking_with_no_email_releases_the_room_and_sends_nothing(): void
    {
        Mail::fake();

        $booking = $this->bookingWithoutEmail();
        $booking->room->forceFill(['is_booked' => 'booked'])->save();

        app(BookingWorkflowService::class)->rejectBooking($booking, 'No availability');

        $this->assertSame('rejected', $booking->fresh()->status);
        $this->assertSame('no', $booking->room->fresh()->is_booked);

        Mail::assertNothingSent();
    }

    public function test_an_email_still_gets_the_confirmation_when_one_is_present(): void
    {
        Mail::fake();

        $booking = $this->bookingWithoutEmail();
        $booking->update(['guest_email' => 'guest@example.test']);

        app(BookingWorkflowService::class)->confirmBooking($booking);

        Mail::assertSent(BookingConfirmedMail::class, 1);
        Mail::assertNotSent(BookingDeclinedMail::class);
    }

    public function test_the_admin_form_saves_a_phone_only_booking(): void
    {
        Mail::fake();

        $this->actingAs($this->staffUser());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $room = $this->room();

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'room_id' => $room->id,
                'room_type' => $room->room_name,
                'guest_name' => 'Telephone Guest',
                'guest_phone' => '08039998888',
                'check_in' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'check_out' => now()->addDays(4)->format('Y-m-d H:i:s'),
                'status' => 'pending',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::query()->where('guest_phone', '08039998888')->firstOrFail();

        $this->assertNull($booking->guest_email);
        $this->assertSame('Telephone Guest', $booking->guest_name);
    }

    public function test_the_admin_form_still_demands_a_phone_number(): void
    {
        $this->actingAs($this->staffUser());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $room = $this->room();

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'room_id' => $room->id,
                'room_type' => $room->room_name,
                'guest_name' => 'No Contact Guest',
                'guest_phone' => '',
                'guest_email' => 'someone@example.test',
                'check_in' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'check_out' => now()->addDays(4)->format('Y-m-d H:i:s'),
                'status' => 'pending',
            ])
            ->call('create')
            ->assertHasFormErrors(['guest_phone' => 'required']);

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_the_admin_form_rejects_a_malformed_email_when_one_is_given(): void
    {
        // Optional must not mean unvalidated.
        $this->actingAs($this->staffUser());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $room = $this->room();

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'room_id' => $room->id,
                'room_type' => $room->room_name,
                'guest_name' => 'Typo Guest',
                'guest_phone' => '08039998888',
                'guest_email' => 'not-an-address',
                'check_in' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'check_out' => now()->addDays(4)->format('Y-m-d H:i:s'),
                'status' => 'pending',
            ])
            ->call('create')
            ->assertHasFormErrors(['guest_email' => 'email']);
    }
}
