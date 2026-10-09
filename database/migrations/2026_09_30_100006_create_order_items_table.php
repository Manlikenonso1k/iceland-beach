<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Line items snapshot name, price and prep time at order time rather than
 * joining the meal live — a renamed or repriced meal must not rewrite history,
 * the same reasoning as referral_ticket_sales.commission_rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items')) {
            return;
        }

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('meal_id')->constrained('meals');
            $table->string('name_snapshot');
            $table->unsignedBigInteger('unit_price'); // kobo
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('prep_time_minutes_snapshot');
            $table->string('notes')->nullable(); // "no pepper"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
