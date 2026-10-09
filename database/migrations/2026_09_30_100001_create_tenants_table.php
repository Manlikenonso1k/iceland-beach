<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hospi Sales multi-tenancy root. Every new Hospi Sales table carries a
 * tenant_id leading its compound indexes; the tenant a request may touch is
 * resolved from the authenticated Sanctum token's user, never from the request.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants')) {
            return;
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('primary_colour')->default('#002F61');
            $table->json('enabled_departments')->nullable();
            $table->string('timezone')->default('Africa/Lagos');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
