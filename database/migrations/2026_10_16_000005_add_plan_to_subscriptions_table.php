<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `plan` ENUM column to Cashier's `subscriptions` table.
 *
 * IMPORTANT: This migration assumes `php artisan cashier:install` has already
 * been run, so the base `subscriptions` table exists. Cashier publishes its
 * own migrations with timestamps earlier than the date prefix on this file.
 *
 * The `plan` column is the denormalized cache of the user's effective plan.
 * The authoritative derivation (per docs/data-model.md §7.1) is a query
 * against `stripe_status` and `ends_at`; this column exists for fast reads
 * and is kept in sync by the Stripe webhook handler (separate change).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('plan', ['free', 'pro'])
                ->default('free')
                ->after('stripe_status');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }
};
