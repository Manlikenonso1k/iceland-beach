<?php

namespace Tests\Feature;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\SpaceBookingResource;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingDeclinedMail;
use App\Models\ServiceBooking;
use App\Models\SpaceBooking;
use App\Models\User;
use App\Services\SpaceBookingWorkflowService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Space Host owns space bookings; Front Desk keeps rooms. Each may VIEW the
 * other's area but never change it.
 *
 * Read-only is enforced on the SERVER, not by hiding buttons — these tests hit
 * create and edit URLs directly, and drive the confirm action through Livewire,
 * because that is how a hidden button gets bypassed.
 */
class SpaceBookingAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($roleName, 'web');

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function space(string $name = 'Beach Cabana 1', float $price = 50000): ServiceBooking
    {
        return ServiceBooking::query()->create([
            'service_name' => $name,
            'service_category' => 'Cabana',
            'service_price' => $price,
            'is_booked' => 'no',
        ]);
    }

    private function spaceBooking(?ServiceBooking $space = null, string $status = 'pending'): SpaceBooking
    {
        $space ??= $this->space();

        return SpaceBooking::query()->create([
            'space_id' => $space->id,
            'space_name' => $space->service_name,
            'guest_name' => 'Ada Guest',
            'guest_email' => 'ada@example.test',
            'guest_phone' => '08031234567',
            'guests' => 20,
            'check_in' => now()->addDays(3),
            'check_out' => now()->addDays(5),
            'status' => $status,
        ]);
    }

    // ---------------------------------------------------------------- Space Host

    public function test_space_host_can_reach_the_space_booking_area(): void
    {
        $host = $this->userWithRole('Space Host');

        foreach (['/admin/space-bookings', '/admin/space-bookings/list', '/admin/space-bookings/create'] as $url) {
            $this->actingAs($host)->get($url)->assertOk();
        }

        $booking = $this->spaceBooking();

        $this->actingAs($host)
            ->get('/admin/space-bookings/' . $booking->id . '/edit')
            ->assertOk();
    }

    public function test_space_host_can_confirm_and_deny_space_bookings(): void
    {
        Mail::fake();

        $host = $this->userWithRole('Space Host');
        $this->actingAs($host);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertTrue(SpaceBookingResource::canWrite());

        $confirmed = $this->spaceBooking();
        app(SpaceBookingWorkflowService::class)->confirmBooking($confirmed);

        $this->assertSame('confirmed', $confirmed->fresh()->status);
        // The hold is mirrored onto the legacy catalogue row.
        $this->assertSame('booked', $confirmed->space->fresh()->is_booked);
        Mail::assertSent(BookingConfirmedMail::class, 1);

        $denied = $this->spaceBooking($this->space('Beach Cabana 2'));
        app(SpaceBookingWorkflowService::class)->rejectBooking($denied, 'Double-booked');

        $this->assertSame('rejected', $denied->fresh()->status);
        $this->assertSame('Double-booked', $denied->fresh()->rejection_reason);
        // A denial must never leave the space held.
        $this->assertSame('no', $denied->space->fresh()->is_booked);
        Mail::assertSent(BookingDeclinedMail::class, 1);
    }

    public function test_space_host_may_view_room_bookings_but_not_change_them(): void
    {
        $host = $this->userWithRole('Space Host');

        // View is allowed — the cross-view is read-only, not invisible.
        $this->actingAs($host)->get('/admin/bookings')->assertOk();
        $this->actingAs($host)->get('/admin/bookings/list')->assertOk();

        // Writing is refused at the server, by direct URL.
        $this->actingAs($host)->get('/admin/bookings/create')->assertForbidden();

        $this->actingAs($host);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertFalse(BookingResource::canWriteRoomBooking());
    }

    // ---------------------------------------------------------------- Front Desk

    public function test_front_desk_rooms_still_work_exactly_as_before(): void
    {
        $frontDesk = $this->userWithRole('Front Desk');

        foreach (['/admin/bookings', '/admin/bookings/list', '/admin/bookings/create'] as $url) {
            $this->actingAs($frontDesk)->get($url)->assertOk();
        }

        $this->actingAs($frontDesk);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertTrue(BookingResource::canWriteRoomBooking());
    }

    public function test_front_desk_may_view_space_bookings_but_not_change_them(): void
    {
        $frontDesk = $this->userWithRole('Front Desk');

        $this->actingAs($frontDesk)->get('/admin/space-bookings')->assertOk();
        $this->actingAs($frontDesk)->get('/admin/space-bookings/list')->assertOk();

        $this->actingAs($frontDesk)->get('/admin/space-bookings/create')->assertForbidden();

        $booking = $this->spaceBooking();
        $this->actingAs($frontDesk)
            ->get('/admin/space-bookings/' . $booking->id . '/edit')
            ->assertForbidden();

        $this->actingAs($frontDesk);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertFalse(SpaceBookingResource::canWrite());
    }

    // ---------------------------------------------------------------- Admin

    public function test_super_admin_has_full_access_to_both(): void
    {
        $admin = $this->userWithRole('Super Admin');

        foreach ([
            '/admin/bookings', '/admin/bookings/create',
            '/admin/space-bookings', '/admin/space-bookings/list', '/admin/space-bookings/create',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertTrue(SpaceBookingResource::canWrite());
        $this->assertTrue(BookingResource::canWriteRoomBooking());
    }
}
