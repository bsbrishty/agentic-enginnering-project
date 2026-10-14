<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();

            // user_id is nullable: an event may not map to a user (e.g. a
            // platform-level event). cascadeOnDelete cleans up the audit log
            // when the user is deleted.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            // subscription_id uses nullOnDelete: Cashier may have already
            // pruned the row by the time a late event arrives, and we want
            // to keep the audit log intact.
            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('subscriptions')
                ->nullOnDelete();

            // The idempotency key. Webhook handlers MUST check this column
            // before processing an event.
            $table->string('stripe_event_id')->unique();

            $table->string('type');
            $table->json('payload');

            // Null until the handler has run successfully. Used to detect
            // half-processed events during debugging.
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_events');
    }
};
