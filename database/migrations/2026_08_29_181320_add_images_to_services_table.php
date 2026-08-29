<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uploadable imagery for spaces (the `services` table).
 *
 * Mirrors the room columns: the legacy `service_image` holds public-folder paths
 * that are not on the storage disk, so uploads get their own columns and
 * ServiceBooking::displayImage() falls back to the legacy value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            if (! Schema::hasColumn('services', 'main_image')) {
                $table->string('main_image')->nullable()->after('service_image');
            }

            if (! Schema::hasColumn('services', 'gallery_images')) {
                $table->json('gallery_images')->nullable()->after('main_image');
            }

            if (! Schema::hasColumn('services', 'description')) {
                $table->text('description')->nullable()->after('service_category');
            }

            if (! Schema::hasColumn('services', 'capacity')) {
                $table->unsignedInteger('capacity')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            foreach (['main_image', 'gallery_images', 'description', 'capacity'] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
