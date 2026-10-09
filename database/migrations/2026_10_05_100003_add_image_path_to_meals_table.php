<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product image for a meal (uploaded by a manager). Nullable string path on the
 * public disk; the API serves a request-host URL so the same image resolves for
 * a phone over the LAN and for the browser.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meals', function (Blueprint $table): void {
            if (! Schema::hasColumn('meals', 'image_path')) {
                $table->string('image_path')->nullable()->after('sku');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meals', function (Blueprint $table): void {
            if (Schema::hasColumn('meals', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
