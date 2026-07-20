<?php

namespace App\Console\Commands;

use App\Services\Templates\TemplateOverrideStore;
use Illuminate\Console\Command;

/**
 * Rebuild the override files from the database.
 *
 * A deploy rsyncs code, not storage, and a restore brings back rows without
 * bringing back derived files. This puts the two back in step. The store
 * self-heals on boot as well; this exists so the repair can be run
 * deliberately, and so a release script can call it.
 */
class SyncTemplateOverrides extends Command
{
    protected $signature = 'templates:sync';

    protected $description = 'Rebuild template override files from the database';

    public function handle(TemplateOverrideStore $store): int
    {
        $count = $store->syncAll();

        $this->info("Rebuilt {$count} template override(s) into " . $store->basePath() . '.');

        return self::SUCCESS;
    }
}
