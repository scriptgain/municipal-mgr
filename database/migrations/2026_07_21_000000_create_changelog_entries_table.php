<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Release Notes storage.
 *
 * Dated, versioned entries describing what shipped, written for town staff and
 * residents rather than operators. The body is Markdown, rendered server-side
 * on the public page; summary is the one-line teaser shown in the timeline.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('changelog_entries')) {
            Schema::create('changelog_entries', function (Blueprint $table) {
                $table->id();
                $table->string('version', 40);
                $table->date('released_on')->index();
                $table->string('title', 200);
                $table->string('summary', 500)->nullable();
                $table->text('body')->nullable();
                $table->boolean('is_published')->default(true)->index();
                $table->timestamps();
            });

            return;
        }

        // Idempotent column guards for re-runs against an existing table.
        Schema::table('changelog_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('changelog_entries', 'version')) {
                $table->string('version', 40);
            }
            if (! Schema::hasColumn('changelog_entries', 'released_on')) {
                $table->date('released_on')->index();
            }
            if (! Schema::hasColumn('changelog_entries', 'title')) {
                $table->string('title', 200);
            }
            if (! Schema::hasColumn('changelog_entries', 'summary')) {
                $table->string('summary', 500)->nullable();
            }
            if (! Schema::hasColumn('changelog_entries', 'body')) {
                $table->text('body')->nullable();
            }
            if (! Schema::hasColumn('changelog_entries', 'is_published')) {
                $table->boolean('is_published')->default(true)->index();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelog_entries');
    }
};
