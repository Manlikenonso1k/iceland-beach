<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simple key/value settings store, used first for the booking payment toggle.
 *
 * `type` records how to cast the value back out, since everything is stored as
 * text.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string'); // string | boolean | integer
                $table->timestamps();
            });
        }

        // Default OFF: enabling payment is a deliberate act, never a side
        // effect of deploying.
        DB::table('settings')->updateOrInsert(
            ['key' => 'booking_payment_required'],
            ['value' => '0', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
