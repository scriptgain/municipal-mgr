<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use Illuminate\Console\Command;

class Housekeeping extends Command
{
    protected $signature = 'municipal:housekeeping';

    protected $description = 'Prune audit logs and login attempts past their retention window.';

    public function handle(): int
    {
        // Retention defaults to a year: long enough to answer a records request,
        // short enough that the table does not become the biggest thing here.
        $days = (int) config('municipal.audit_log_days', 365);
        if ($days > 0) {
            $n = AuditLog::where('created_at', '<', now()->subDays($days))->delete();
            $this->info("Pruned {$n} audit log row(s) older than {$days} days.");
        }

        $n = LoginAttempt::where('created_at', '<', now()->subDays(30))->delete();
        $this->info("Pruned {$n} login attempt row(s).");

        return self::SUCCESS;
    }
}
