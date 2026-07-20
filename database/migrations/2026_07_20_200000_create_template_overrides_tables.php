<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template Manager storage.
 *
 * Overrides live in the DATABASE, never in resources/views. This product
 * self-updates from signed ScriptGain releases: a release that rewrote
 * resources/views would silently destroy a municipality's customisation, and a
 * customer edit sitting in a tracked file would block the release from
 * applying cleanly. Keeping the override in a row sidesteps both, and makes
 * "reset to shipped default" a single DELETE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('template_overrides')) {
            Schema::create('template_overrides', function (Blueprint $table) {
                $table->id();
                // Dot-notation Blade view name, e.g. site.home or components.site.hero.
                $table->string('view', 191)->unique();
                $table->longText('content');
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('template_versions')) {
            Schema::create('template_versions', function (Blueprint $table) {
                $table->id();
                $table->string('view', 191)->index();
                $table->longText('content');
                // save | revert | reset | import. Kept so the history reads as a
                // story rather than a pile of identical rows.
                $table->string('action', 20)->default('save');
                $table->string('note', 255)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('template_versions');
        Schema::dropIfExists('template_overrides');
    }
};
