<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('room')) {
            return;
        }

        Schema::table('room', function (Blueprint $table): void {
            if (! Schema::hasColumn('room', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('total_price');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `room` MODIFY `is_booked` ENUM('no','booked','rejected','expired') NOT NULL DEFAULT 'no'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('room')) {
            return;
        }

        Schema::table('room', function (Blueprint $table): void {
            if (Schema::hasColumn('room', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `room` MODIFY `is_booked` ENUM('no','booked','expired') NOT NULL DEFAULT 'no'");
        }
    }
};
