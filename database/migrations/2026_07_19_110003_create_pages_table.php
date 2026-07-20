<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* CMS pages. Body content is a list of section blocks (see
   config('municipal.sections')) stored as JSON, which is what makes the
   page builder possible without a per-block table. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages')) {
            return;
        }
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->json('sections')->nullable();     // [{type, heading, body, items:[]}]
            $table->string('template')->default('standard'); // standard|wide|landing
            $table->string('status')->default('draft');      // draft|published
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('meta_description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('show_in_nav')->default(false);
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
