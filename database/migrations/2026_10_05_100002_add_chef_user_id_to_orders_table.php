<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The chef an order is attributed to — resolved from the active shift at
 * placement, and pinned to the accepting chef on accept if still null. Nullable:
 * an order placed with no chef on station simply has none yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'chef_user_id')) {
                $table->foreignId('chef_user_id')->nullable()->after('placed_by_user_id')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'chef_user_id')) {
                $table->dropConstrainedForeignId('chef_user_id');
            }
        });
    }
};
