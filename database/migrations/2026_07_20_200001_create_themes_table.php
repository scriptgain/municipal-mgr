<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Theme Manager storage.
 *
 * A theme is a named bag of design tokens. It is stored as JSON rather than a
 * column per token so a municipality can export a theme, hand the file to a
 * neighbouring install, and import it without a schema migration in between.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('themes')) {
            Schema::create('themes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->string('slug', 80)->unique();
                $table->string('description', 255)->nullable();
                $table->boolean('is_active')->default(false)->index();
                // Presets ship with the product. They can be duplicated and
                // activated but never edited away, so a municipality always has
                // a known-good look to fall back to.
                $table->boolean('is_preset')->default(false);
                $table->json('tokens');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        $this->seedPresets();
    }

    /**
     * Install the shipped presets.
     *
     * Done here so the feature demonstrates itself the moment an install
     * migrates, rather than only once an admin happens to open the screen. The
     * first preset is Civic Navy, whose token set is empty and therefore
     * resolves to exactly the values already in resources/css/app.css: making
     * it active changes nothing about how the site renders.
     */
    private function seedPresets(): void
    {
        if (DB::table('themes')->exists()) {
            return;
        }

        $now = now();
        foreach (config('themes.presets', []) as $index => $preset) {
            DB::table('themes')->insert([
                'name' => $preset['name'],
                'slug' => $preset['slug'],
                'description' => $preset['description'] ?? null,
                'is_active' => $index === 0,
                'is_preset' => true,
                'tokens' => json_encode($preset['tokens'] ?? []),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
