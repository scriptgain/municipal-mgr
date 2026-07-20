<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Staff-logged contact: the phone call, counter visit, email or letter that
   never generated a service request but is still part of the record. Without
   it the timeline only shows what the resident filed online, which is a small
   fraction of how a town hall really talks to people. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('constituent_interactions')) {
            return;
        }
        Schema::create('constituent_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constituent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');                    // phone_call|counter_visit|email|letter|meeting|other
            $table->string('direction')->default('inbound'); // inbound|outbound
            $table->string('subject')->nullable();
            $table->text('note');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['constituent_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constituent_interactions');
    }
};
