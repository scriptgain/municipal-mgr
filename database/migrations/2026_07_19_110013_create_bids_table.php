<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Procurement: bids, RFPs, RFQs. Deadlines here are legally binding, so the
   closing timestamp is stored to the minute and shown with its timezone. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bids')) {
            return;
        }
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('reference')->nullable();       // RFP 2026-07
            $table->string('bid_type')->default('Bid');     // Bid|RFP|RFQ|Sole Source
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->dateTime('pre_bid_meeting_at')->nullable();
            $table->string('status')->default('open');      // open|closed|awarded|cancelled
            $table->string('awarded_to')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['status', 'closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
