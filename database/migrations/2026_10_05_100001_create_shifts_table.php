<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chef shifts. A chef opens a shift for a department; orders placed into that
 * department while the shift is open are attributed to that chef. An open shift
 * has ended_at = null. Additive, tenant-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shifts')) {
            return;
        }

        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('department');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'department', 'ended_at']);
            $table->index(['tenant_id', 'user_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
