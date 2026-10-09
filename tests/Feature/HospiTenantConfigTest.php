<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HospiTenantConfigTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug), 'slug' => $slug, 'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen'], 'timezone' => 'Africa/Lagos', 'is_active' => true,
        ]);
    }

    private function user(string $role, Tenant $t): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($role, 'web');
        $u = User::query()->create([
            'name' => $role, 'email' => strtolower($role).'-'.$t->slug.'@hospi.test',
            'password' => Hash::make('password'), 'tenant_id' => $t->id,
        ]);
        $u->assignRole($role);

        return $u;
    }

    private function auth(User $u): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$u->createToken('t')->plainTextToken);
    }

    public function test_manager_can_set_enabled_departments(): void
    {
        $t = $this->tenant('alpha');
        $manager = $this->user('Manager', $t);

        $this->auth($manager)->patchJson('/api/tenant', [
            'enabled_departments' => ['grill_kitchen', 'main_kitchen', 'ice_cream'],
        ])->assertOk()
            ->assertJsonPath('tenant.enabled_departments', ['main_kitchen', 'grill_kitchen', 'ice_cream']); // catalogue order

        $this->assertEqualsCanonicalizing(
            ['main_kitchen', 'grill_kitchen', 'ice_cream'],
            $t->fresh()->enabled_departments,
        );
    }

    public function test_waiter_cannot_configure_departments(): void
    {
        $t = $this->tenant('alpha');
        $waiter = $this->user('Waiter', $t);

        $this->auth($waiter)->patchJson('/api/tenant', [
            'enabled_departments' => ['main_kitchen'],
        ])->assertForbidden();
    }

    public function test_unknown_department_is_rejected(): void
    {
        $t = $this->tenant('alpha');
        $manager = $this->user('Manager', $t);

        $this->auth($manager)->patchJson('/api/tenant', [
            'enabled_departments' => ['teleport_kitchen'],
        ])->assertStatus(422);
    }

    public function test_empty_department_list_is_rejected(): void
    {
        $t = $this->tenant('alpha');
        $manager = $this->user('Manager', $t);

        $this->auth($manager)->patchJson('/api/tenant', [
            'enabled_departments' => [],
        ])->assertStatus(422);
    }

    public function test_me_reflects_the_new_config(): void
    {
        $t = $this->tenant('alpha');
        $manager = $this->user('Manager', $t);

        $this->auth($manager)->patchJson('/api/tenant', [
            'enabled_departments' => ['grill_kitchen', 'barbecue'],
        ])->assertOk();

        $this->auth($manager)->getJson('/api/me')
            ->assertJsonPath('tenant.enabled_departments', ['grill_kitchen', 'barbecue']);
    }
}
