<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HospiMealManageTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private function tenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => ucfirst($slug), 'slug' => $slug, 'primary_colour' => '#002F61',
            'enabled_departments' => ['main_kitchen', 'grill_kitchen'], 'timezone' => 'Africa/Lagos', 'is_active' => true,
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

    public function test_manager_creates_a_meal_with_price_in_naira(): void
    {
        $t = $this->tenant('alpha');
        $manager = $this->user('Manager', $t);

        $this->auth($manager)->postJson('/api/meals', [
            'department' => 'grill_kitchen',
            'name' => 'Grilled Lobster',
            'category' => 'Grill',
            'price_naira' => 12500,
            'prep_time_minutes' => 40,
        ])->assertCreated()
            ->assertJsonPath('data.price', 1250000) // kobo
            ->assertJsonPath('data.is_available', true);
    }

    public function test_waiter_cannot_create_a_meal(): void
    {
        $t = $this->tenant('alpha');
        $waiter = $this->user('Waiter', $t);

        $this->auth($waiter)->postJson('/api/meals', [
            'department' => 'grill_kitchen', 'name' => 'X', 'price_naira' => 100, 'prep_time_minutes' => 10,
        ])->assertForbidden();
    }

    public function test_manager_updates_price_and_availability(): void
    {
        $t = $this->tenant('alpha');
        $manager = $this->user('Manager', $t);
        $meal = Tenant::runAs($t->id, fn () => Meal::query()->create([
            'department' => 'main_kitchen', 'name' => 'Jollof', 'price' => 450000, 'prep_time_minutes' => 25, 'is_available' => true,
        ]));

        $this->auth($manager)->patchJson("/api/meals/{$meal->id}", [
            'price_naira' => 5000, 'is_available' => false,
        ])->assertOk()
            ->assertJsonPath('data.price', 500000)
            ->assertJsonPath('data.is_available', false);
    }

    public function test_duplicate_name_in_same_station_is_rejected(): void
    {
        $t = $this->tenant('alpha');
        $manager = $this->user('Manager', $t);
        Tenant::runAs($t->id, fn () => Meal::query()->create([
            'department' => 'main_kitchen', 'name' => 'Jollof', 'price' => 450000, 'prep_time_minutes' => 25, 'is_available' => true,
        ]));

        $this->auth($manager)->postJson('/api/meals', [
            'department' => 'main_kitchen', 'name' => 'Jollof', 'price_naira' => 5000, 'prep_time_minutes' => 25,
        ])->assertStatus(422);
    }

    public function test_manager_uploads_a_meal_image(): void
    {
        Storage::fake('public');
        $t = $this->tenant('alpha');
        $manager = $this->user('Manager', $t);
        $meal = Tenant::runAs($t->id, fn () => Meal::query()->create([
            'department' => 'main_kitchen', 'name' => 'Jollof', 'price' => 450000, 'prep_time_minutes' => 25, 'is_available' => true,
        ]));

        $this->auth($manager)->postJson("/api/meals/{$meal->id}/image", [
            'image_base64' => 'data:image/png;base64,'.self::PNG_1X1,
            'mime' => 'image/png',
        ])->assertOk()->assertJsonPath('data.image_url', fn ($url) => is_string($url) && str_contains($url, '/storage/meals/'));

        $this->assertNotNull($meal->fresh()->image_path);
        Storage::disk('public')->assertExists($meal->fresh()->image_path);
    }

    public function test_waiter_only_sees_available_meals(): void
    {
        $t = $this->tenant('alpha');
        $waiter = $this->user('Waiter', $t);
        Tenant::runAs($t->id, function () {
            Meal::query()->create(['department' => 'main_kitchen', 'name' => 'Live', 'price' => 1, 'prep_time_minutes' => 5, 'is_available' => true]);
            Meal::query()->create(['department' => 'main_kitchen', 'name' => 'Hidden', 'price' => 1, 'prep_time_minutes' => 5, 'is_available' => false]);
        });

        $names = collect($this->auth($waiter)->getJson('/api/meals?department=main_kitchen')->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Live'));
        $this->assertFalse($names->contains('Hidden'));
    }
}
