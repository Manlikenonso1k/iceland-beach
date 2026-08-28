<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('ticket_type');          // "Regular" or "VIP"
            $table->integer('quantity')->default(1);
            $table->unsignedBigInteger('amount');   // stored in kobo
            $table->string('order_ref')->unique();  // e.g. BEACH_xxxxxxxx
            $table->string('payment_gateway');      // "paystack" or "titan"
            $table->string('payment_ref')->nullable();
            $table->boolean('paid')->default(false);
            $table->boolean('checked_in')->default(false);
            $table->string('qr_code_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
