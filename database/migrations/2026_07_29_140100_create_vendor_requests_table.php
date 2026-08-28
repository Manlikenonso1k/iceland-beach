<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_spot_id')->constrained('vendor_spots')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('address');
            $table->text('expected_to_sell');
            $table->string('order_ref')->nullable();
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_requests');
    }
};
