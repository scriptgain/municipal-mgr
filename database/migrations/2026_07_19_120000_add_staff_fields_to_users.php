<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* Staff accounts: role, department scoping (for department editors), 2FA, and
   password age. Column-guarded so a re-install or partial upgrade is safe. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('viewer')->after('email');
            }
            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title')->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('job_title');
            }
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            }
            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });

        // department_id needs the departments table to exist first.
        if (Schema::hasTable('departments') && ! Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('role')
                    ->constrained()->nullOnDelete();
            });
        }

        // A pre-existing install's accounts become admins, never lockouts.
        DB::table('users')->whereNull('password_changed_at')->update(['password_changed_at' => now()]);
        DB::table('users')->where('role', '')->orWhereNull('role')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['job_title', 'phone', 'two_factor_secret', 'two_factor_confirmed_at', 'password_changed_at', 'is_active'] as $c) {
                if (Schema::hasColumn('users', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
