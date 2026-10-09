<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant-scoped tables / cabanas / sunbeds a waitress can send an order to.
 * Separate from the legacy single-tenant dining_tables (which is tied to the POS
 * waiters table); this is the Hospi Sales multi-tenant equivalent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('venue_tables')) {
            return;
        }

        Schema::create('venue_tables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('zone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_tables');
    }
};
