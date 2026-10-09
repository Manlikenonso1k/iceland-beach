<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 0 stop-gate: auth, tenant identity, and tenant isolation. Every later
 * phase inherits this scoping, so it is proven end to end before anything else.
 */
class HospiPhase0Test extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(string $slug, array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => ucfirst($slug).' Beach',
            'slug' => $slug,
            'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen', 'barbecue', 'gate_sales'],
            'timezone' => 'Africa/Lagos',
            'is_active' => true,
        ], $overrides));
    }

    private function makeUser(string $role, Tenant $tenant, array $overrides = []): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($role, 'web');

        $user = User::query()->create(array_merge([
            'name' => $role.' User',
            'email' => strtolower($role).'-'.$tenant->slug.'@hospi.test',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
        ], $overrides));

        $user->assignRole($role);

        return $user;
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_health_endpoint_is_public_and_reports_status(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['status' => 'ok', 'version' => 'hospi-sales-v1'])
            ->assertJsonStructure(['status', 'version', 'env', 'time']);
    }

    public function test_login_with_email_returns_a_token(): void
    {
        $tenant = $this->makeTenant('alpha');
        $this->makeUser('Manager', $tenant, ['email' => 'ada@hospi.test']);

        $this->postJson('/api/auth/login', [
            'login' => 'ada@hospi.test',
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'roles']]);
    }

    public function test_login_with_phone_returns_a_token(): void
    {
        $tenant = $this->makeTenant('alpha');
        $this->makeUser('Waiter', $tenant, ['email' => 'phone@hospi.test', 'phone' => '08099999999']);

        $this->postJson('/api/auth/login', [
            'login' => '08099999999',
            'password' => 'password',
        ])->assertOk()->assertJsonPath('user.roles.0', 'Waiter');
    }

    public function test_login_rejects_bad_credentials(): void
    {
        $tenant = $this->makeTenant('alpha');
        $this->makeUser('Manager', $tenant, ['email' => 'ada@hospi.test']);

        $this->postJson('/api/auth/login', [
            'login' => 'ada@hospi.test',
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    public function test_me_returns_tenant_identity_and_prices_from_the_token(): void
    {
        $tenant = $this->makeTenant('alpha', ['name' => 'Alpha Resort']);
        Tenant::runAs($tenant->id, fn () => TenantSetting::set('gate_ticket', 300000, 'integer'));

        $user = $this->makeUser('Manager', $tenant);

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($user))
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('tenant.name', 'Alpha Resort')
            ->assertJsonPath('tenant.primary_colour', '#002F61')
            ->assertJsonPath('prices.gate_ticket', 300000)
            ->assertJsonPath('user.roles.0', 'Manager');
    }

    public function test_me_prices_never_leak_another_tenants_values(): void
    {
        $alpha = $this->makeTenant('alpha');
        $beta = $this->makeTenant('beta');
        Tenant::runAs($alpha->id, fn () => TenantSetting::set('gate_ticket', 111100, 'integer'));
        Tenant::runAs($beta->id, fn () => TenantSetting::set('gate_ticket', 999900, 'integer'));

        $alphaManager = $this->makeUser('Manager', $alpha);

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alphaManager))
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('prices.gate_ticket', 111100);
    }

    public function test_global_scope_isolates_rows_by_tenant(): void
    {
        $alpha = $this->makeTenant('alpha');
        $beta = $this->makeTenant('beta');

        Tenant::runAs($alpha->id, fn () => TenantSetting::set('a_only', 'A', 'string'));
        Tenant::runAs($beta->id, fn () => TenantSetting::set('b_only', 'B', 'string'));

        Tenant::runAs($alpha->id, function () use ($beta): void {
            $this->assertSame(1, TenantSetting::query()->count());
            $this->assertSame('A', TenantSetting::get('a_only'));
            // Beta's row is invisible and unreachable from Alpha's context.
            $this->assertNull(TenantSetting::get('b_only'));
            $this->assertNull(TenantSetting::query()->where('tenant_id', $beta->id)->first());
        });
    }

    public function test_creating_a_scoped_model_stamps_the_callers_tenant(): void
    {
        $alpha = $this->makeTenant('alpha');

        Tenant::runAs($alpha->id, function () use ($alpha): void {
            $setting = TenantSetting::query()->create(['key' => 'k', 'value' => 'v', 'type' => 'string']);
            $this->assertSame($alpha->id, $setting->tenant_id);
        });
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $tenant = $this->makeTenant('alpha');
        $user = $this->makeUser('Manager', $tenant);
        $token = $this->tokenFor($user);

        $this->assertSame(1, $user->tokens()->count());

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')->assertOk();

        // Revocation = the token row is gone, so it can never authenticate again.
        $this->assertSame(0, $user->fresh()->tokens()->count());
    }
}
