<?php

namespace App\Console\Commands;

use App\Models\ArrestRecord;
use App\Models\AuditLog;
use App\Services\RecordsSettings;
use Illuminate\Console\Command;

/**
 * Auto-expiry for the public blotter.
 *
 * The public read scope already hides a record past its retention window, so
 * this is not what keeps expired records off the site: it is what makes the
 * expiry real in the database, so the admin list, exports, and any future API
 * all agree with what the public sees.
 */
class ExpireArrestRecords extends Command
{
    protected $signature = 'records:expire {--dry-run : List what would be unpublished and change nothing}';

    protected $description = 'Unpublish arrest records whose public retention window has passed';

    public function handle(): int
    {
        if (! RecordsSettings::enabled()) {
            $this->info('Jail And Arrest Records module is disabled. Nothing to do.');

            return self::SUCCESS;
        }

        $lapsed = ArrestRecord::retentionLapsed()->get();

        if ($lapsed->isEmpty()) {
            $this->info('No records past their retention window.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');

        foreach ($lapsed as $record) {
            $this->line(($dry ? '[dry-run] ' : '') . "Unpublishing {$record->reference()} (booked {$record->booked_at?->toDateString()})");

            if ($dry) {
                continue;
            }

            $record->forceFill([
                'is_published' => false,
                'unpublish_reason' => 'Retention window of ' . RecordsSettings::retentionDays() . ' days elapsed',
            ])->saveQuietly();

            AuditLog::record('unpublished', "Arrest record {$record->reference()} auto-unpublished: retention window elapsed", $record);
        }

        $this->info(($dry ? 'Would unpublish ' : 'Unpublished ') . $lapsed->count() . ' record(s).');

        return self::SUCCESS;
    }
}
