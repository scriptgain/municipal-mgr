<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-entity SEO fields.
 *
 * Every publicly reachable content type gets the same five columns so one
 * service, one admin panel, and one sitemap builder can treat them uniformly.
 * Pages already shipped a meta_description, so every column is added behind a
 * hasColumn guard and this migration is safe to re-run.
 *
 * All five are nullable with no backfill on purpose: a blank field means
 * "derive it from the content", which HasSeo does at render time. That keeps an
 * unedited municipal site correct without writing derived text into the DB,
 * where it would silently go stale the moment an editor changed the story.
 */
return new class extends Migration
{
    /** Tables that carry a public URL and therefore carry SEO fields. */
    private const TABLES = [
        'pages',
        'news_posts',
        'notices',
        'events',
        'departments',
        'meetings',
        'job_postings',
        'bids',
        'files',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'meta_title')) {
                    $t->string('meta_title', 255)->nullable();
                }
                if (! Schema::hasColumn($table, 'meta_description')) {
                    $t->string('meta_description', 500)->nullable();
                }
                if (! Schema::hasColumn($table, 'og_image')) {
                    $t->string('og_image', 255)->nullable();
                }
                if (! Schema::hasColumn($table, 'canonical_url')) {
                    $t->string('canonical_url', 255)->nullable();
                }
                if (! Schema::hasColumn($table, 'noindex')) {
                    $t->boolean('noindex')->default(false);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            // meta_description is deliberately NOT dropped from pages: it
            // predates this migration and holds operator-entered text.
            $columns = ['meta_title', 'og_image', 'canonical_url', 'noindex'];
            if ($table !== 'pages') {
                $columns[] = 'meta_description';
            }

            $columns = array_values(array_filter(
                $columns,
                fn (string $c) => Schema::hasColumn($table, $c)
            ));

            if ($columns) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn($columns));
            }
        }
    }
};
