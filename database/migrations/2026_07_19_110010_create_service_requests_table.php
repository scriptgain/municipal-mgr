<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Report-An-Issue intake. `reference` + `tracking_token` let a resident check
   status without an account — a login wall on a pothole report is the fastest
   way to make residents stop reporting potholes. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_requests')) {
            return;
        }
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();          // SR-2026-000412
            $table->string('tracking_token', 64)->unique();
            $table->string('category');
            $table->longText('description');
            $table->string('location_text')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('reporter_phone')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('status')->default('new');
            $table->string('priority')->default('normal');  // low|normal|high|urgent
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
