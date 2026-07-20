<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Staff-managed navigation. `menu` partitions the primary navbar, the footer
   columns, and the homepage quick-links tiles into one editable table. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu_items')) {
            return;
        }
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('menu')->default('primary'); // primary|footer|quicklinks|utility
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('label');
            $table->string('url')->nullable();          // used when page_id is null
            $table->string('icon')->nullable();
            $table->string('description')->nullable();   // quick-link tile subtitle
            $table->boolean('new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['menu', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
