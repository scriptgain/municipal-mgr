<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Deferred FKs: these columns point at tables created later in the run
   (documents, staff_members), so the constraints are attached once everything
   exists. Guarded so a re-run is a no-op. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents') || ! Schema::hasTable('staff_members')) {
            return;
        }

        Schema::table('notices', function (Blueprint $table) {
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->foreign('agenda_document_id')->references('id')->on('documents')->nullOnDelete();
            $table->foreign('minutes_document_id')->references('id')->on('documents')->nullOnDelete();
            $table->foreign('packet_document_id')->references('id')->on('documents')->nullOnDelete();
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->foreign('application_document_id')->references('id')->on('documents')->nullOnDelete();
        });

        Schema::table('bids', function (Blueprint $table) {
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_staff_id')->references('id')->on('staff_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notices', fn (Blueprint $t) => $t->dropForeign(['document_id']));
        Schema::table('meetings', function (Blueprint $t) {
            $t->dropForeign(['agenda_document_id']);
            $t->dropForeign(['minutes_document_id']);
            $t->dropForeign(['packet_document_id']);
        });
        Schema::table('job_postings', fn (Blueprint $t) => $t->dropForeign(['application_document_id']));
        Schema::table('bids', fn (Blueprint $t) => $t->dropForeign(['document_id']));
        Schema::table('departments', fn (Blueprint $t) => $t->dropForeign(['head_staff_id']));
    }
};
