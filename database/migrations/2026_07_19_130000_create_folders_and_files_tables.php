<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Unified File Manager, part 1 of 3: schema.
|
| Creates the two tables that replace document_categories, documents, and
| media_items. The legacy tables are deliberately NOT dropped here (or
| anywhere in this series) — they stay in place as the rollback path until an
| operator is satisfied the new library is correct.
|
| Guarded with hasTable/hasColumn throughout so a re-run is a no-op, per fleet
| convention.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('folders')) {
            Schema::create('folders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('folders')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('description')->nullable();
                $table->string('icon')->default('folder');
                $table->unsignedInteger('sort_order')->default(0);
                // Staff-only folders never appear in the public browser, even
                // when a file inside them is marked public.
                $table->boolean('is_public')->default(true);
                $table->timestamps();
                $table->index(['parent_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('files')) {
            Schema::create('files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('folder_id')->nullable()->constrained('folders')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();

                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('keywords')->nullable();
                $table->string('reference')->nullable();   // Ordinance 2026-14, Resolution 88...

                $table->string('path');                    // path on the public disk
                $table->string('file_name');               // original name, used for downloads
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->string('alt_text')->nullable();    // accessibility: required for content images

                $table->date('document_date')->nullable();
                $table->unsignedBigInteger('download_count')->default(0);
                $table->boolean('is_published')->default(true);
                $table->string('visibility', 16)->default('public');  // public | staff
                $table->string('kind', 16)->default('other');         // image | document | other

                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_published', 'visibility']);
                $table->index(['folder_id', 'kind']);
                $table->index(['kind', 'created_at']);
                $table->index('document_date');
            });
        }
    }

    public function down(): void
    {
        // files first: it holds the FK into folders.
        Schema::dropIfExists('files');
        Schema::dropIfExists('folders');
    }
};
