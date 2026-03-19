<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->string('customer_name');
            $table->string('customer_address')->nullable();
            $table->string('telephone')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('total_in_words')->nullable();
            $table->string('bank_name')->default('MONIEPOINT');
            $table->string('bank_account_number')->default('5029208012');
            $table->string('bank_account_name')->default('NEW ICELAND BEACH RESORT');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_date', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
