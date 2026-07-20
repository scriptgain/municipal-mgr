<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Legal public notices (hearings, ordinances, elections). Kept separate from
   news because posting/expiry dates are statutory, not editorial. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notices')) {
            return;
        }
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable();  // FK added after documents exists
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('notice_type')->default('General'); // Public Hearing|Ordinance|Election|General
            $table->longText('body')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('published');
            $table->timestamps();
            $table->index(['status', 'posted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
