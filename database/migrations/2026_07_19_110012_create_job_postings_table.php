<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_postings')) {
            return;
        }
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('employment_type')->default('Full Time'); // Full Time|Part Time|Seasonal|Volunteer
            $table->string('salary_range')->nullable();
            $table->longText('description')->nullable();
            $table->longText('requirements')->nullable();
            $table->string('apply_url')->nullable();
            $table->string('apply_email')->nullable();
            $table->unsignedBigInteger('application_document_id')->nullable();
            $table->date('posted_on')->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->boolean('is_open_until_filled')->default(false);
            $table->string('status')->default('published');
            $table->timestamps();
            $table->index(['status', 'closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
