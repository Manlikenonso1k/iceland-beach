<?php

namespace Tests\Feature;

use App\Filament\Resources\SpaceResource\Pages\CreateSpace;
use App\Models\DiningTable;
use App\Models\Sale;
use App\Models\ServiceBooking;
use App\Models\User;
use App\Models\Waiter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Production threw "Unknown column services.updated_at" when saving a space and
 * "Unknown column sales.deleted_at" on the Sales page, while migrate:status
 * reported nothing pending.
 *
 * The cause is that several tables were imported from the legacy PHP app with
 * the `migrations` table already stamped, so Laravel believes migrations ran
 * that never built anything. The repo's migrations are internally consistent;
 * only the live database had drifted.
 *
 * These tests therefore do two different jobs:
 *
 *  1. The CONTRACT tests derive their expectations from the models themselves
 *     and assert the schema satisfies them. They are what would have caught
 *     this class of bug, and they catch the next one — including a brand-new
 *     model whose migration forgets timestamps.
 *
 *  2. The DRIFT tests physically drop the columns to reproduce the production
 *     shape, confirm the documented failure, then re-run the migration and
 *     confirm it repairs it. Without these, a test on SQLite would pass
 *     whether or not the migration existed, because the create migrations
 *     already declare the columns here.
 */
class SchemaModelContractTest extends TestCase
{
    use RefreshDatabase;

    /** The migration under test. */
    private const MIGRATION = 'database/migrations/2026_10_21_090000_backfill_timestamp_and_softdelete_columns.php';

    /**
     * Runs the migration's up() DIRECTLY.
     *
     * `artisan migrate` is useless here: RefreshDatabase has already recorded
     * this migration as run, so the runner skips it and the test would pass
     * without proving anything.
     */
    private function runBackfillMigration(): void
    {
        $migration = require base_path(self::MIGRATION);

        $migration->up();
    }

    // ------------------------------------------------------------- contract

    /**
     * Every model keeping timestamps must have the columns to keep them in.
     *
     * Derived from the models, not a hardcoded list, so a new model with a
     * forgetful migration fails here rather than in production.
     */
    public function test_every_model_that_keeps_timestamps_has_the_columns(): void
    {
        $missing = [];

        foreach ($this->models() as $model) {
            if (! $model->usesTimestamps()) {
                continue;
            }

            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (['created_at', 'updated_at'] as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missing[] = $table . '.' . $column . ' (' . $model::class . ')';
                }
            }
        }

        $this->assertSame([], $missing, "Models keep timestamps but the columns do not exist:\n" . implode("\n", $missing));
    }

    /**
     * Every SoftDeletes model must have deleted_at.
     *
     * Without it the trait appends `where deleted_at is null` to EVERY query,
     * so the page does not degrade gracefully — it 500s. That was the Sales
     * page.
     */
    public function test_every_soft_deleting_model_has_a_deleted_at_column(): void
    {
        $missing = [];

        foreach ($this->models() as $model) {
            if (! method_exists($model, 'bootSoftDeletes')) {
                continue;
            }

            $table = $model->getTable();

            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                $missing[] = $table . ' (' . $model::class . ')';
            }
        }

        $this->assertSame([], $missing, "Models use SoftDeletes but have no deleted_at:\n" . implode("\n", $missing));
    }

    // ---------------------------------------------------------------- drift

    public function test_the_migration_restores_a_missing_services_updated_at(): void
    {
        // Reproduce the legacy production shape.
        Schema::table('services', fn ($table) => $table->dropColumn('updated_at'));
        $this->assertFalse(Schema::hasColumn('services', 'updated_at'));

        $this->runBackfillMigration();

        $this->assertTrue(Schema::hasColumn('services', 'updated_at'));

        // And the operation that was failing now works.
        ServiceBooking::query()->create([
            'service_name' => 'Repaired Cabana',
            'service_category' => 'Cabana',
            'service_price' => 50000,
            'is_booked' => 'no',
        ]);

        $this->assertDatabaseHas('services', ['service_name' => 'Repaired Cabana']);
    }

    public function test_the_migration_restores_a_missing_sales_deleted_at(): void
    {
        Schema::table('sales', fn ($table) => $table->dropColumn('deleted_at'));
        $this->assertFalse(Schema::hasColumn('sales', 'deleted_at'));

        $this->runBackfillMigration();

        $this->assertTrue(Schema::hasColumn('sales', 'deleted_at'));

        // SoftDeletes appends `where deleted_at is null`; this is the query
        // that was 500ing the Sales page.
        $this->assertSame(0, Sale::query()->count());
    }

    public function test_the_migration_is_a_no_op_when_nothing_is_missing(): void
    {
        // It will be re-run by every future deploy, and may be run twice by
        // hand on a database that is already correct.
        $before = collect(['services', 'sales', 'room', 'products'])
            ->mapWithKeys(fn (string $t): array => [$t => Schema::getColumnListing($t)])
            ->all();

        $this->runBackfillMigration();

        foreach ($before as $table => $columns) {
            $this->assertSame($columns, Schema::getColumnListing($table), "Columns on {$table} changed");
        }
    }

    public function test_room_is_covered_for_soft_deletes_only(): void
    {
        // App\Models\Room sets $timestamps = false, so `room` is deliberately
        // absent from NEEDS_TIMESTAMPS — the migration must never add timestamp
        // columns there and invite code to assume they are maintained. It does
        // still need deleted_at, because Room uses SoftDeletes.
        //
        // create_rooms_table happens to declare timestamps anyway, so this
        // asserts the migration is a no-op on the table rather than that the
        // columns are absent.
        $before = Schema::getColumnListing('room');

        Schema::table('room', fn ($table) => $table->dropColumn('deleted_at'));
        $this->assertFalse(Schema::hasColumn('room', 'deleted_at'));

        $this->runBackfillMigration();

        $this->assertTrue(Schema::hasColumn('room', 'deleted_at'));
        $this->assertSame(
            collect($before)->sort()->values()->all(),
            collect(Schema::getColumnListing('room'))->sort()->values()->all(),
            'The migration must restore deleted_at and add nothing else to room.',
        );
    }

    // ----------------------------------------------------- the two reported flows

    public function test_saving_a_space_with_an_image_works(): void
    {
        Storage::fake('public');

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Super Admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateSpace::class)
            ->fillForm([
                'service_name' => 'Sunset Marquee',
                'service_category' => 'Marquee',
                'service_price' => 250000,
                'capacity' => 200,
                'is_booked' => 'no',
                'main_image' => [UploadedFile::fake()->image('marquee.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $space = ServiceBooking::query()->where('service_name', 'Sunset Marquee')->firstOrFail();

        // The write that was throwing "Unknown column services.updated_at".
        $this->assertNotNull($space->updated_at);
        $this->assertNotNull($space->main_image);
    }

    public function test_the_sales_list_loads(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Super Admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $waiter = Waiter::query()->create([
            'full_name' => 'Test Waiter',
            'username' => 'testwaiter',
            'password_hash' => 'x',
            'pin_hash' => 'x',
            'role' => 'waiter',
            'is_active' => true,
        ]);

        $table = DiningTable::query()->create([
            'table_name' => 'T1',
            'assigned_waiter_id' => $waiter->id,
            'status' => 'available',
        ]);

        Sale::query()->create([
            'waiter_id' => $waiter->id,
            'table_id' => $table->id,
            'total_amount' => 12500,
            'payment_method' => 'cash',
            'sale_date' => now(),
        ]);

        // The page that was throwing "Unknown column sales.deleted_at".
        $this->actingAs($admin)
            ->get('/admin/sales')
            ->assertOk()
            ->assertSee('Test Waiter');
    }

    /**
     * Every Eloquent model in app/Models, instantiated.
     *
     * @return list<\Illuminate\Database\Eloquent\Model>
     */
    private function models(): array
    {
        $models = [];

        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)) {
                continue;
            }

            $models[] = new $class;
        }

        $this->assertNotEmpty($models, 'No models were discovered — the contract tests would be vacuous.');

        return $models;
    }
}
