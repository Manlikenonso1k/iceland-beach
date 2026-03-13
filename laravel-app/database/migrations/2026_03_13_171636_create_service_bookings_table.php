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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('service_category')->nullable();
            $table->decimal('service_price', 12, 2)->default(0);
            $table->string('service_image')->nullable();
            $table->enum('is_booked', ['no', 'booked', 'expired'])->default('no');
            $table->string('customers_name')->nullable();
            $table->string('customers_email')->nullable();
            $table->dateTime('signin')->nullable();
            $table->dateTime('signout')->nullable();
            $table->unsignedInteger('no_of_people')->nullable();
            $table->boolean('mailsent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_booked', 'signin', 'signout']);
            $table->index('customers_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
