<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->unique();
            $table->string('room_category')->nullable();
            $table->decimal('room_price', 12, 2)->default(0);
            $table->string('room_image')->nullable();
            $table->enum('is_booked', ['no', 'booked', 'rejected', 'expired'])->default('no');
            $table->string('customer_name')->nullable();
            $table->string('email')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('mailsent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_booked', 'start_date', 'end_date']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room');
    }
};
