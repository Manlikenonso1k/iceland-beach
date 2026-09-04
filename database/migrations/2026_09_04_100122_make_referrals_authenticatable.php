<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns `referrals` into its own authenticatable table.
 *
 * Partners previously signed in as Users carrying a 'Referral' role, which let
 * them into /admin alongside staff. They now authenticate against this table
 * through a dedicated guard and a separate panel at /referral.
 *
 * `user_id` is kept but made nullable: it is legacy, no longer used for auth,
 * and dropping a constrained FK buys nothing when partners are provisioned
 * fresh. `created_by` still points at the staff User who provisioned them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table): void {
            if (! Schema::hasColumn('referrals', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('referrals', 'email')) {
                $table->string('email')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('referrals', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }

            if (! Schema::hasColumn('referrals', 'password')) {
                $table->string('password')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('referrals', 'remember_token')) {
                // Required by Authenticatable for "remember me" sessions.
                $table->rememberToken();
            }
        });

        // Auth no longer flows through a linked User.
        if (Schema::hasColumn('referrals', 'user_id')) {
            Schema::table('referrals', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table): void {
            foreach (['name', 'phone', 'password', 'remember_token'] as $column) {
                if (Schema::hasColumn('referrals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('referrals', 'email')) {
            Schema::table('referrals', function (Blueprint $table): void {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            });
        }
    }
};
