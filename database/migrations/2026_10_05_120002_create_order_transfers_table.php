<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hand-offs of an order between staff. kind = 'server' (waitress → waitress,
 * changes who owns/serves it) or 'chef' (chef → chef in the same station,
 * changes who's cooking it). The recipient accepts or declines.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_transfers')) {
            return;
        }

        Schema::create('order_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('kind'); // server | chef
            $table->foreignId('from_user_id')->constrained('users');
            $table->foreignId('to_user_id')->constrained('users');
            $table->string('status')->default('pending'); // pending | accepted | declined
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'to_user_id', 'status']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_transfers');
    }
};
