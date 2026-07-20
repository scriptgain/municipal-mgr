<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Posted meetings with agenda + minutes + video. Open-meetings law makes this
   the single most-visited section of most municipal sites. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meetings')) {
            return;
        }
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('body');                // Town Council, Planning & Zoning...
            $table->string('title')->nullable();   // "Regular Meeting", "Special Session"
            $table->string('slug')->unique();
            $table->dateTime('meets_at');
            $table->string('location')->nullable();
            $table->string('address')->nullable();
            $table->longText('summary')->nullable();
            $table->unsignedBigInteger('agenda_document_id')->nullable();
            $table->unsignedBigInteger('minutes_document_id')->nullable();
            $table->unsignedBigInteger('packet_document_id')->nullable();
            $table->string('video_url')->nullable();
            $table->string('status')->default('scheduled'); // scheduled|cancelled|held
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['body', 'meets_at']);
            $table->index(['is_published', 'meets_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
