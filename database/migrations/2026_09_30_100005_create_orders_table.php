<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The order/delay engine's core table. due_at is stamped once at insert
 * (placed_at + target_minutes) and target_minutes is snapshotted from the meal's
 * prep_time_minutes AT ORDER TIME — a later prep-time change must never move a
 * past order's SLA. notified_overdue_at makes the overdue push fire exactly once.
 *
 * status/department are strings, not DB enums (SQLite/MySQL divergence — see the
 * meals migration). The traffic-light colour is computed from these timestamps
 * server-side, never stored, so it cannot drift.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            return;
        }

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('department'); // main_kitchen | barbecue | ice_cream
            $table->foreignId('placed_by_user_id')->constrained('users');
            $table->unsignedBigInteger('table_id')->nullable(); // dining_tables.id
            $table->string('guest_ref')->nullable(); // room number or name when no table
            $table->string('status')->default('pending'); // pending|accepted|declined|ready|completed|cancelled

            $table->timestamp('placed_at');
            $table->unsignedInteger('target_minutes');
            $table->timestamp('due_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->string('decline_reason')->nullable();
            $table->foreignId('declined_by_user_id')->nullable()->constrained('users');

            $table->unsignedBigInteger('total_amount')->default(0); // kobo
            $table->timestamp('notified_overdue_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'due_at']);      // Order Board
            $table->index(['tenant_id', 'department', 'status']);  // kitchen queue
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
