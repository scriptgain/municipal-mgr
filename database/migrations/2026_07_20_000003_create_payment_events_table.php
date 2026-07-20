<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Received Stripe webhook events.
 *
 * `stripe_event_id` is UNIQUE, and that uniqueness is the idempotency
 * mechanism: Stripe retries deliveries, and a retried event must never apply
 * a second time.
 *
 * Deliberately stores a short summary, NOT the payload. Webhook payloads carry
 * cardholder names, billing addresses and email addresses, and a government
 * system has no business keeping resident PII in a debug table forever.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_events')) {
            return;
        }

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id', 80)->unique();
            $table->string('type', 80);
            $table->string('stripe_account_id', 80)->nullable();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('summary', 255)->nullable();
            $table->boolean('handled')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
