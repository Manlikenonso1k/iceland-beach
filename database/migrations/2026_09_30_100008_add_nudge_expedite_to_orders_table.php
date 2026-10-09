<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manager escalation stamps: last_nudged_at (manager paged the kitchen about an
 * overdue order) and expedited_at (manager flagged an in-progress order to jump
 * the queue). Additive and nullable — existing orders are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'last_nudged_at')) {
                $table->timestamp('last_nudged_at')->nullable()->after('notified_overdue_at');
            }
            if (! Schema::hasColumn('orders', 'expedited_at')) {
                $table->timestamp('expedited_at')->nullable()->after('last_nudged_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'last_nudged_at')) {
                $table->dropColumn('last_nudged_at');
            }
            if (Schema::hasColumn('orders', 'expedited_at')) {
                $table->dropColumn('expedited_at');
            }
        });
    }
};
