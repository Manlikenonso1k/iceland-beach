<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per successfully-paid ticket attributed to a referral.
 *
 * ticket_id is UNIQUE: a ticket can only ever be credited once. The four code
 * paths that mark a ticket paid (Paystack verify, TGI callback, TGI webhook and
 * the background refresh service) can all fire for the same ticket, so this
 * constraint — not caller discipline — is what guarantees no double credit.
 *
 * Only successful payments are recorded at all; failed ones never reach here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_ticket_sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('referral_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->unique()->constrained()->cascadeOnDelete();

            // Snapshot of the ticket total in kobo, matching tickets.amount.
            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status', 20)->default('successful');
            $table->timestamps();

            $table->index(['referral_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_ticket_sales');
    }
};
