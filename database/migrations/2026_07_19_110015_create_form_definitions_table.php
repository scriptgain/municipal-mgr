<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Forms builder. Field schema is JSON so staff can add a "Dog License
   Renewal" form without a developer or a migration. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_definitions')) {
            return;
        }
        Schema::create('form_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('fields')->nullable();  // [{key,label,type,required,options,help}]
            $table->string('notify_email')->nullable();
            $table->text('success_message')->nullable();
            $table->boolean('store_submissions')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_definitions');
    }
};
