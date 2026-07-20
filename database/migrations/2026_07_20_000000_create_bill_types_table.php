<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bill types: utility, permit, fine, other — and whatever else the town bills
 * for. Staff-configurable rather than a hardcoded enum, because every
 * municipality has one charge nobody else has.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bill_types')) {
            return;
        }

        Schema::create('bill_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('label', 120);
            $table->text('description')->nullable();
            $table->string('icon', 40)->default('file-text');

            // Whether paying this type requires looking a bill up by reference.
            // A permit fee typically does not; a water bill always does.
            $table->boolean('requires_lookup')->default(true);

            // Whether a resident may pay this type without a bill reference,
            // typing the amount themselves (clamped by min/max below).
            $table->boolean('allows_open_payment')->default(false);

            $table->unsignedBigInteger('min_amount_cents')->nullable();
            $table->unsignedBigInteger('max_amount_cents')->nullable();

            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_types');
    }
};
