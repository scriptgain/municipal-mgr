<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Document library: ordinances, budgets, forms, minutes, permits. Full-text
   search runs over title + description + keywords (portable across
   MySQL/SQLite via LIKE; no engine-specific index required). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documents')) {
            return;
        }
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('keywords')->nullable();
            $table->string('reference')->nullable();      // Ordinance 2026-14, Resolution 88...
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->date('document_date')->nullable();
            $table->unsignedBigInteger('download_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_published', 'document_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
