<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Emergency / advisory banner shown site-wide. Scheduling windows mean staff
   can stage a storm advisory ahead of time instead of at 2am. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('alerts')) {
            return;
        }
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('level')->default('info');   // info|advisory|warning|emergency
            $table->string('link_url')->nullable();
            $table->string('link_label')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_dismissible')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
