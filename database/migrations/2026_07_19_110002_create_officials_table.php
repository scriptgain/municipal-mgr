<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Elected officials / council members. Terms are tracked because "who was on
   council in 2019" is a routine public-records question. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('officials')) {
            return;
        }
        Schema::create('officials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('office');                       // Mayor, Council Member, Clerk...
            $table->string('district')->nullable();          // Ward 3, At-Large...
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_current')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['is_current', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officials');
    }
};
