<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\JobPosting;
use App\Models\NewsPost;
use App\Models\Page;
use Illuminate\Console\Command;

/**
 * Time-based publishing. Municipal content is full of "this goes live Monday"
 * and "this closes at 4:00 PM Friday", and nobody should have to be at a
 * keyboard for either.
 */
class PublishDue extends Command
{
    protected $signature = 'municipal:publish-due';

    protected $description = 'Publish scheduled content and retire expired alerts and postings.';

    public function handle(): int
    {
        $pages = Page::where('status', 'scheduled')
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $news = NewsPost::where('status', 'scheduled')
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $alerts = Alert::where('is_active', true)
            ->whereNotNull('ends_at')->where('ends_at', '<', now())
            ->update(['is_active' => false]);

        $jobs = JobPosting::where('status', 'published')
            ->where('is_open_until_filled', false)
            ->whereNotNull('closes_at')->where('closes_at', '<', now())
            ->update(['status' => 'closed']);

        if ($pages + $news + $alerts + $jobs > 0) {
            $this->info("Published {$pages} page(s), {$news} post(s); retired {$alerts} alert(s), {$jobs} posting(s).");
        }

        return self::SUCCESS;
    }
}
