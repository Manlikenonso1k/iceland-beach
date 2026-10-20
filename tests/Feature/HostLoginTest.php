<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "Host can't log in — wrong password."
 *
 * The password was never wrong. Filament's Login::authenticate() calls the SAME
 * throwFailureValidationException() for a failed password AND for a successful
 * password followed by a canAccessPanel() refusal — so a role missing from
 * User::PANEL_ROLES is indistinguishable from a typo.
 *
 * `Host` was missing, because the shipped role is named `Space Host`. It is now
 * accepted as an alias.
 *
 * These tests go through the real Filament user form rather than a factory,
 * because the form is what the bug report used.
 */
class HostLoginTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Super Admin', 'web');

        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    /** Creates a user through the actual Filament form. */
    private function createViaForm(string $email, string $password, string $role): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $roleModel = Role::findOrCreate($role, 'web');

        $this->actingAs($this->superAdmin());
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Form Created Host',
                'email' => $email,
                'password' => $password,
                'roles' => [$roleModel->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        return User::query()->where('email', strtolower(trim($email)))->firstOrFail();
    }

    public function test_the_form_hashes_the_password_exactly_once(): void
    {
        // host.md suspected double hashing. It is not possible here: Laravel's
        // 'hashed' cast checks Hash::isHashed() first. This pins that.
        $user = $this->createViaForm('host1@example.test', 'secret-password', 'Space Host');

        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertNotSame('secret-password', $user->password);
    }

    public function test_a_space_host_created_through_the_form_can_log_in(): void
    {
        $user = $this->createViaForm('host2@example.test', 'secret-password', 'Space Host');

        $this->assertTrue(auth()->guard('web')->attempt([
            'email' => 'host2@example.test',
            'password' => 'secret-password',
        ]));

        // Authenticating is only half of it — Filament also requires this.
        $this->assertTrue($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));

        $this->actingAs($user)->get('/admin/space-bookings')->assertOk();
    }

    public function test_a_role_named_plainly_host_also_works(): void
    {
        // The actual reported bug: a user given a role named 'Host' rather than
        // 'Space Host' authenticated fine and was then refused the panel, which
        // Filament reports as a credentials error.
        $user = $this->createViaForm('host3@example.test', 'secret-password', 'Host');

        $this->assertTrue(auth()->guard('web')->attempt([
            'email' => 'host3@example.test',
            'password' => 'secret-password',
        ]));

        $this->assertTrue($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));

        $this->actingAs($user)->get('/admin/space-bookings')->assertOk();
    }

    public function test_host_alias_also_grants_space_booking_writes(): void
    {
        $user = $this->createViaForm('host4@example.test', 'secret-password', 'Host');

        $this->actingAs($user);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        // Would otherwise be silently read-only, which is the same bug wearing
        // a different hat.
        $this->assertTrue(\App\Filament\Resources\SpaceBookingResource::canWrite());
        $this->actingAs($user)->get('/admin/space-bookings/create')->assertOk();
    }

    public function test_an_email_with_whitespace_or_capitals_is_normalised(): void
    {
        // A trailing space in the form produced an account nobody could log
        // into, because the login lookup is an exact string comparison.
        $user = $this->createViaForm('  HOST5@Example.TEST  ', 'secret-password', 'Space Host');

        $this->assertSame('host5@example.test', $user->email);

        $this->assertTrue(auth()->guard('web')->attempt([
            'email' => 'host5@example.test',
            'password' => 'secret-password',
        ]));
    }

    public function test_a_role_with_no_panel_access_is_still_refused(): void
    {
        // The alias must not become a blanket allow.
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Waiter', 'web');

        $user = User::factory()->create(['email' => 'waiter@example.test']);
        $user->assignRole('Waiter');

        $this->assertFalse($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));
    }

    public function test_editing_a_user_without_touching_the_password_keeps_it(): void
    {
        $user = $this->createViaForm('host6@example.test', 'secret-password', 'Space Host');
        $hashBefore = $user->password;

        $this->actingAs($this->superAdmin());
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        Livewire::test(\App\Filament\Resources\UserResource\Pages\EditUser::class, ['record' => $user->id])
            ->fillForm(['name' => 'Renamed Host', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Renamed Host', $user->name);
        // A blank password field must not blank or re-hash the password.
        $this->assertSame($hashBefore, $user->password);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_the_reset_password_command_hashes_once_and_reports_access(): void
    {
        $user = $this->createViaForm('host7@example.test', 'old-password', 'Space Host');

        $this->artisan('user:reset-password', ['email' => 'host7@example.test'])
            ->expectsQuestion('New password', 'brand-new-password')
            ->expectsQuestion('Confirm new password', 'brand-new-password')
            ->expectsOutputToContain('Password updated.')
            ->assertSuccessful();

        $user->refresh();

        $this->assertTrue(Hash::check('brand-new-password', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));
    }

    public function test_the_reset_password_command_rejects_a_mismatch(): void
    {
        $user = $this->createViaForm('host8@example.test', 'old-password', 'Space Host');
        $hashBefore = $user->password;

        $this->artisan('user:reset-password', ['email' => 'host8@example.test'])
            ->expectsQuestion('New password', 'one-password')
            ->expectsQuestion('Confirm new password', 'different-password')
            ->assertFailed();

        $this->assertSame($hashBefore, $user->fresh()->password);
    }
}
