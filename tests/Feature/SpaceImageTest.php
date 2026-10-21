<?php

namespace Tests\Feature;

use App\Filament\Resources\SpaceResource\Pages\EditSpace;
use App\Models\ServiceBooking;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Space images uploaded in /admin/spaces were not appearing on /services —
 * every space showed the same legacy fallback, /legacy-images/Coconut Cabana.jpeg.
 *
 * Root cause: Api\CatalogController::services() never selected `main_image`, so
 * the column holding the upload was not even fetched, and the React page read
 * the legacy `service_image` column instead of a resolved URL. With both empty
 * for a newly uploaded space, the front end's single hardcoded fallback was
 * used for every row.
 *
 * Note the upload itself was always fine: the Filament FileUpload has used
 * ->disk('public')->directory('spaces/main') throughout, and
 * ServiceBooking::displayImage() already resolved it correctly. Only the API
 * and the front end were wrong.
 */
class SpaceImageTest extends TestCase
{
    use RefreshDatabase;

    private const LEGACY_FALLBACK = '/legacy-images/Coconut Cabana.jpeg';

    private function superAdmin(): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Super Admin', 'web');

        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    private function space(string $name, array $overrides = []): ServiceBooking
    {
        return ServiceBooking::query()->create(array_merge([
            'service_name' => $name,
            'service_category' => 'Cabana',
            'service_price' => 50000,
            'is_booked' => 'no',
        ], $overrides));
    }

    public function test_an_uploaded_main_image_reaches_the_services_api(): void
    {
        Storage::fake('public');

        $space = $this->space('Coconut Cabana');

        $this->actingAs($this->superAdmin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // Uploaded through the real admin form, which is how the bug was hit.
        Livewire::test(EditSpace::class, ['record' => $space->id])
            ->fillForm(['main_image' => [UploadedFile::fake()->image('cabana.jpg')]])
            ->call('save')
            ->assertHasNoFormErrors();

        $space->refresh();

        $this->assertNotNull($space->main_image, 'The upload should have been stored.');
        $this->assertStringStartsWith('spaces/main/', $space->main_image);
        Storage::disk('public')->assertExists($space->main_image);

        $payload = collect($this->getJson('/api/services')->assertOk()->json())
            ->firstWhere('service_name', 'Coconut Cabana');

        // The column must be selected — it was not, which is the whole bug.
        $this->assertArrayHasKey('main_image', $payload);

        // And the resolved public URL must be present, not the raw path.
        $expected = Storage::disk('public')->url($space->main_image);

        $this->assertSame($expected, $payload['image_url']);
        $this->assertStringContainsString('/storage/spaces/main/', $payload['image_url']);
        $this->assertNotSame(self::LEGACY_FALLBACK, $payload['image_url']);
    }

    public function test_every_space_gets_its_own_image_not_one_shared_fallback(): void
    {
        // The reported symptom was that ALL spaces showed the same picture, so
        // one space passing is not enough.
        Storage::fake('public');

        foreach (['Cabana One', 'Cabana Two', 'Cabana Three'] as $index => $name) {
            $space = $this->space($name);
            $path = 'spaces/main/space-' . $index . '.jpg';
            Storage::disk('public')->put($path, 'fake-image-bytes');
            $space->update(['main_image' => $path]);
        }

        $payload = collect($this->getJson('/api/services')->assertOk()->json());

        $urls = $payload->pluck('image_url');

        $this->assertCount(3, $urls);
        // Distinct, and none of them the fallback.
        $this->assertSame(3, $urls->unique()->count(), 'Each space must resolve to its own image.');
        $this->assertFalse($urls->contains(self::LEGACY_FALLBACK));

        foreach ($urls as $url) {
            $this->assertStringContainsString('/storage/spaces/main/', $url);
        }
    }

    public function test_the_legacy_image_is_used_only_when_no_upload_exists(): void
    {
        Storage::fake('public');

        $this->space('Legacy Only', ['service_image' => 'legacy-images/Old Cabana.jpeg']);

        $payload = collect($this->getJson('/api/services')->assertOk()->json())
            ->firstWhere('service_name', 'Legacy Only');

        $this->assertSame('legacy-images/Old Cabana.jpeg', $payload['image_url']);
    }

    public function test_an_upload_takes_precedence_over_a_stale_legacy_path(): void
    {
        // A space migrated from the old site keeps its service_image. Once an
        // admin uploads a replacement, the upload must win.
        Storage::fake('public');

        $space = $this->space('Both Set', ['service_image' => 'legacy-images/Old Cabana.jpeg']);
        Storage::disk('public')->put('spaces/main/new.jpg', 'fake-image-bytes');
        $space->update(['main_image' => 'spaces/main/new.jpg']);

        $payload = collect($this->getJson('/api/services')->assertOk()->json())
            ->firstWhere('service_name', 'Both Set');

        $this->assertStringContainsString('/storage/spaces/main/new.jpg', $payload['image_url']);
        $this->assertStringNotContainsString('Old Cabana', $payload['image_url']);
    }

    public function test_a_space_with_no_image_at_all_returns_null_not_a_raw_path(): void
    {
        // The front end supplies the fallback for null. The API must not invent
        // a URL that would 404.
        Storage::fake('public');

        $this->space('No Image');

        $payload = collect($this->getJson('/api/services')->assertOk()->json())
            ->firstWhere('service_name', 'No Image');

        $this->assertNull($payload['image_url']);
    }

    public function test_gallery_images_are_returned_as_resolved_urls(): void
    {
        Storage::fake('public');

        $space = $this->space('With Gallery');

        Storage::disk('public')->put('spaces/main/main.jpg', 'x');
        Storage::disk('public')->put('spaces/gallery/g1.jpg', 'x');
        Storage::disk('public')->put('spaces/gallery/g2.jpg', 'x');

        $space->update([
            'main_image' => 'spaces/main/main.jpg',
            'gallery_images' => ['spaces/gallery/g1.jpg', 'spaces/gallery/g2.jpg'],
        ]);

        $payload = collect($this->getJson('/api/services')->assertOk()->json())
            ->firstWhere('service_name', 'With Gallery');

        $this->assertCount(3, $payload['gallery_urls'], 'Main image plus two gallery images.');

        foreach ($payload['gallery_urls'] as $url) {
            $this->assertStringStartsWith('/storage/', parse_url($url, PHP_URL_PATH) ?: '');
        }
    }

    public function test_the_rooms_api_still_resolves_its_own_images(): void
    {
        // Rooms already worked this way; this change must not disturb them.
        Storage::fake('public');

        \App\Models\Room::query()->create([
            'room_name' => 'Deluxe 101',
            'room_category' => 'Deluxe',
            'room_price' => 80000,
            'is_booked' => 'no',
        ]);

        $payload = collect($this->getJson('/api/rooms')->assertOk()->json())
            ->firstWhere('room_name', 'Deluxe 101');

        $this->assertArrayHasKey('image_url', $payload);
        $this->assertArrayHasKey('gallery_urls', $payload);
    }
}
