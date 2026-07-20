<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Jail And Arrest Records.
|
| Three tables:
|   arrest_records     the booking itself, plus its disposition and retention
|   arrest_charges     one row per charge (a booking is rarely a single count)
|   expungement_logs   proof a court-ordered removal was carried out
|
| Notes on what is deliberately NOT here:
|   - No date of birth. Age at booking is stored instead; a DOB is a stolen
|     identity waiting to happen and the public interest is served by an age.
|   - No soft deletes on arrest_records. An expungement order is an order to
|     destroy the record, and a deleted_at column is not a destroyed record.
|     expungement_logs carries the compliance trail instead, WITHOUT the name.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('arrest_records')) {
            Schema::create('arrest_records', function (Blueprint $table) {
                $table->id();

                // Public URLs key on a random ref, not a name slug: a blotter
                // whose URLs are guessable names is a search-engine problem.
                $table->string('public_ref', 16)->unique();

                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('last_name');
                $table->string('suffix', 20)->nullable();

                // Age at booking. Under config('records.minimum_publish_age')
                // the record can never be published (see ArrestRecord::booted).
                $table->unsignedSmallInteger('age')->nullable();

                $table->dateTime('booked_at');
                $table->dateTime('released_at')->nullable();

                $table->string('arresting_agency');
                $table->string('case_number')->nullable();
                $table->string('booking_number')->nullable();
                $table->decimal('bond_amount', 12, 2)->nullable();
                $table->string('bond_note')->nullable();

                $table->string('custody_status')->default('in_custody');
                $table->string('disposition')->default('pending');
                $table->string('disposition_note')->nullable();
                $table->dateTime('disposition_updated_at')->nullable();

                $table->string('mugshot_path')->nullable();
                $table->boolean('mugshot_takedown_requested')->default(false);
                $table->string('mugshot_takedown_note')->nullable();

                // Staff-only. Never rendered on a public view.
                $table->text('internal_notes')->nullable();

                $table->boolean('is_published')->default(false);
                $table->dateTime('published_at')->nullable();
                $table->dateTime('retention_expires_at')->nullable();
                $table->string('unpublish_reason')->nullable();

                $table->timestamps();

                $table->index(['is_published', 'booked_at']);
                $table->index(['custody_status', 'booked_at']);
                $table->index('disposition');
                $table->index('retention_expires_at');
                $table->index('last_name');
            });
        }

        if (! Schema::hasTable('arrest_charges')) {
            Schema::create('arrest_charges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('arrest_record_id')->constrained()->cascadeOnDelete();
                $table->string('description');
                $table->string('statute')->nullable();
                $table->string('severity')->default('misdemeanor');
                $table->unsignedSmallInteger('counts')->default(1);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['arrest_record_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('expungement_logs')) {
            Schema::create('expungement_logs', function (Blueprint $table) {
                $table->id();

                // Intentionally NO subject name. The point of an expungement is
                // that the name stops existing in the system; a compliance log
                // that keeps it has quietly defeated the court order. Case and
                // order references are enough to prove the order was executed.
                $table->string('case_number')->nullable();
                $table->string('booking_number')->nullable();
                $table->string('order_reference')->nullable();
                $table->string('ordered_by')->nullable();   // issuing court or authority
                $table->text('reason')->nullable();

                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('performed_by_name')->nullable();  // survives user deletion
                $table->dateTime('performed_at');
                $table->string('ip', 45)->nullable();
                $table->boolean('mugshot_destroyed')->default(false);

                $table->timestamps();
                $table->index('performed_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('arrest_charges');
        Schema::dropIfExists('expungement_logs');
        Schema::dropIfExists('arrest_records');
    }
};
