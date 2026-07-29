<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_spots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(50000);
            $table->decimal('pos_x', 8, 4); // percentage
            $table->decimal('pos_y', 8, 4); // percentage
            $table->enum('status', ['available', 'pending_approval', 'sold'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_spots');
    }
};
