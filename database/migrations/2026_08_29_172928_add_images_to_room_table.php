<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uploadable room imagery, managed from Filament.
 *
 * The existing `room_image` column holds legacy public paths such as
 * "/legacy-images/rooms/Gold Room 1.jpeg" — they are NOT on the `public` storage
 * disk, so pointing a Filament FileUpload at that column would break every
 * current image. These new columns hold uploads instead, and Room::displayImage()
 * prefers main_image while falling back to the legacy path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room', function (Blueprint $table): void {
            if (! Schema::hasColumn('room', 'main_image')) {
                $table->string('main_image')->nullable()->after('room_image');
            }

            if (! Schema::hasColumn('room', 'gallery_images')) {
                $table->json('gallery_images')->nullable()->after('main_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('room', function (Blueprint $table): void {
            foreach (['main_image', 'gallery_images'] as $column) {
                if (Schema::hasColumn('room', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
