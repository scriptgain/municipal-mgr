<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Departments are the spine of a municipal site: staff, documents, services,
   job postings, meetings and service requests all hang off one. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('departments')) {
            return;
        }
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('building');
            $table->string('summary')->nullable();      // one-line, used on cards
            $table->longText('description')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('hours')->nullable();
            $table->unsignedBigInteger('head_staff_id')->nullable(); // -> staff_members
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
