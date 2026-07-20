<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Public staff directory. Distinct from `users` (login accounts): most staff
   listed publicly never sign in, and some admins are not publicly listed. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_members')) {
            return;
        }
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('job_title');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('extension', 16)->nullable();
            $table->string('office')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['department_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
