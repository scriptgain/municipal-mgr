<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments, online and offline.
 *
 * NOTHING card-related is stored here: no PAN, no CVC, no raw token. The only
 * Stripe identifiers kept are the PaymentIntent / Charge / Payout ids, which
 * are references, not credentials, and the last four digits plus brand, which
 * are what a resident needs to recognise their own payment on a receipt.
 *
 * `idempotency_key` is unique and is what stops a double-submitted form or a
 * double-clicked Pay button from producing two charges.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();

            // Nullable: an open payment (permit fee, facility rental) has no
            // bill behind it, only a bill type.
            $table->foreignId('bill_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bill_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('constituent_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('refunded_cents')->default(0);
            $table->string('currency', 3)->default('usd');

            $table->string('status', 24)->default('pending');
            $table->string('method', 24)->default('card');

            // Stripe references. `livemode` is recorded per payment so a test
            // payment can never be mistaken for real money later, whatever the
            // panel's mode happens to be when someone opens the record.
            $table->string('stripe_payment_intent_id', 80)->nullable()->unique();
            $table->string('stripe_charge_id', 80)->nullable();
            $table->string('stripe_payout_id', 80)->nullable();
            $table->string('stripe_account_id', 80)->nullable();
            $table->boolean('livemode')->default(false);

            // Display-only card details. Never sufficient to transact.
            $table->string('card_brand', 24)->nullable();
            $table->string('card_last4', 4)->nullable();

            $table->string('payer_name', 150)->nullable();
            $table->string('payer_email', 150)->nullable();
            $table->string('payer_phone', 40)->nullable();

            $table->string('idempotency_key', 80)->unique();
            $table->string('receipt_token', 64)->unique();
            $table->string('failure_reason', 255)->nullable();
            $table->text('notes')->nullable();

            // Set for offline payments recorded by counter staff.
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('payout_arrival_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'paid_at']);
            $table->index(['bill_id', 'status']);
            $table->index('stripe_payout_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
