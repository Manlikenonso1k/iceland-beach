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
        Schema::create('membership', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('type')->nullable();
            $table->enum('mplan', ['monthly', 'yearly'])->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('member_status', ['paid', 'unpaid', 'expired'])->default('unpaid');
            $table->decimal('total', 12, 2)->default(0);
            $table->integer('duration')->nullable();
            $table->string('pob')->nullable();
            $table->date('dob')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('nationality')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('ename')->nullable();
            $table->string('ephone')->nullable();
            $table->boolean('mailsent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership');
    }
};
