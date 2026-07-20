<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bills issued to residents.
 *
 * Amounts are integer cents. `amount_paid_cents` is maintained by the payment
 * processor rather than recomputed in a view, so the balance a resident is
 * shown and the amount actually charged always come from the same number.
 *
 * `lookup_surname` and `lookup_postal_code` are the second factor on the public
 * lookup form: a reference number alone must never be enough to pull up
 * somebody else's bill.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bills')) {
            return;
        }

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('bill_type_id')->constrained()->cascadeOnDelete();

            // The resident record this bill belongs to, when one matches.
            // Nullable: a bill may be raised against an account before the
            // person behind it is known to the CRM.
            $table->foreignId('constituent_id')->nullable()->constrained()->nullOnDelete();

            // Utility account / permit / citation number as printed on the bill.
            $table->string('account_number', 80)->nullable();

            $table->string('payer_name', 150)->nullable();
            $table->string('payer_email', 150)->nullable();
            $table->string('payer_phone', 40)->nullable();

            // Second-factor material, stored normalised (lowercased, trimmed;
            // postal code digits only) so comparison never depends on how the
            // resident typed it.
            $table->string('lookup_surname', 80)->nullable();
            $table->string('lookup_postal_code', 20)->nullable();

            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('amount_paid_cents')->default(0);
            $table->string('currency', 3)->default('usd');

            $table->string('description', 255)->nullable();
            $table->text('notes')->nullable();

            $table->date('issued_on')->nullable();
            $table->date('due_date')->nullable();

            $table->string('status', 20)->default('unpaid');
            $table->timestamp('paid_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index('account_number');
            $table->index(['bill_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
