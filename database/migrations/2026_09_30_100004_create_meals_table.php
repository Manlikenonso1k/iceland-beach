<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One meals table for every kitchen. Main Kitchen and Barbecue share this shape;
 * the `department` column keeps their catalogues separate, so adding a third
 * kitchen is a data change, not a migration. Price is kobo; prep_time_minutes is
 * manager-editable and drives every countdown.
 *
 * department is a plain string, not a DB enum: SQLite (tests) silently ignores
 * enum constraints while MySQL (prod) enforces them, so an enum would pass here
 * and fail live. Validation lives in the app layer instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meals')) {
            return;
        }

        Schema::create('meals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('department'); // main_kitchen | barbecue | ice_cream
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedBigInteger('price')->default(0); // kobo
            $table->unsignedInteger('prep_time_minutes')->default(30);
            $table->boolean('is_available')->default(true);
            $table->string('sku')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'department', 'name']); // idempotent CSV re-import
            $table->index(['tenant_id', 'department', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
