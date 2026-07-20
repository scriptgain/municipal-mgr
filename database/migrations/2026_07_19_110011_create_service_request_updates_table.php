<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Status history. is_public splits the resident-visible trail from internal
   crew notes, so staff can be candid without publishing it. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_request_updates')) {
            return;
        }
        Schema::create('service_request_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_updates');
    }
};
