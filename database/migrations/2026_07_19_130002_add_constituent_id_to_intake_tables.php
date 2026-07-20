<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Joins the two existing intake tables to the constituent record. Nullable on
   purpose: anonymous reports have no constituent and must keep working. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_requests') && ! Schema::hasColumn('service_requests', 'constituent_id')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->foreignId('constituent_id')->nullable()->after('reporter_phone')
                    ->constrained()->nullOnDelete();
            });
        }

        if (Schema::hasTable('form_submissions') && ! Schema::hasColumn('form_submissions', 'constituent_id')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->foreignId('constituent_id')->nullable()->after('data')
                    ->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_requests') && Schema::hasColumn('service_requests', 'constituent_id')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('constituent_id');
            });
        }

        if (Schema::hasTable('form_submissions') && Schema::hasColumn('form_submissions', 'constituent_id')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('constituent_id');
            });
        }
    }
};
