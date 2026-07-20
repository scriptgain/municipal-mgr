<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* The constituent record: one row per resident the town has ever heard from.
   Service requests and form submissions used to carry a loose name/email pair
   with nothing joining them, so "what has this person filed?" was unanswerable.

   Dedupe keys are stored normalized (`email_key`, `phone_key`) rather than
   computed at query time: residents type "J.Ruiz@Example.com" one week and
   "jruiz@example.com" the next, and an index on the raw column would treat
   those as two people. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('constituents')) {
            return;
        }
        Schema::create('constituents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('email_key')->nullable();     // lowercased + trimmed
            $table->string('phone')->nullable();
            $table->string('phone_key', 20)->nullable(); // digits only, last 10
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 60)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('do_not_contact')->default(false);
            // Optional link to a staff/portal account. Most residents never
            // register, so this stays null for the overwhelming majority.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // 'manual' | 'backfill' | 'service_request' | 'form_submission' | 'seed'
            $table->string('source')->default('manual');
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->unique('email_key');
            $table->index('phone_key');
            $table->index('name');
            $table->index('last_interaction_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constituents');
    }
};
