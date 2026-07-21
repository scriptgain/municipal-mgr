<?php

namespace Database\Seeders;

use App\Models\ChangelogEntry;
use Illuminate\Database\Seeder;

/**
 * Seed the public Release Notes.
 *
 * Written for town staff and residents, not operators: no server paths, no
 * internal detail, just what changed on their website and services. Keyed on
 * version with updateOrCreate and literal date strings so re-running the
 * seeder is stable and never shuffles the dates.
 */
class ChangelogSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            [
                'version' => '0.1.0',
                'released_on' => '2026-07-05',
                'title' => 'A Refreshed Public Website',
                'summary' => 'A new home page, department pages, an events calendar, and clearer navigation.',
                'body' => <<<'MD'
                We rebuilt the public website from the ground up so residents can find what they need faster.

                - A new **home page** that surfaces the most-requested tasks first
                - Dedicated **department pages** with contacts, hours, and services
                - A public **events calendar** and individual event pages
                - Simpler top navigation and a site-wide search
                MD,
                'is_published' => true,
            ],
            [
                'version' => '0.2.0',
                'released_on' => '2026-07-09',
                'title' => 'One Place For Public Documents',
                'summary' => 'A unified file and document manager for agendas, minutes, budgets, and forms.',
                'body' => <<<'MD'
                Public documents now live in a single, organized library instead of scattered links.

                - Browse agendas, minutes, budgets, and forms by folder
                - Clean, shareable links that keep working when documents are reorganized
                - Faster uploads and clearer titles for staff posting new files
                MD,
                'is_published' => true,
            ],
            [
                'version' => '0.3.0',
                'released_on' => '2026-07-14',
                'title' => 'Stronger Spam Protection On Public Forms',
                'summary' => 'Selectable spam protection so online forms and issue reports stay clean.',
                'body' => <<<'MD'
                Every public form, including Report An Issue, is now protected against automated spam.

                - Choose the provider that fits your town: **reCAPTCHA**, **hCaptcha**, **Cloudflare Turnstile**, or a built-in challenge
                - Fewer junk submissions reaching staff inboxes
                - Legitimate residents get through with minimal friction
                MD,
                'is_published' => true,
            ],
            [
                'version' => '0.3.5',
                'released_on' => '2026-07-17',
                'title' => 'Make The Site Your Own',
                'summary' => 'Site theming and a template editor to match your community\'s look.',
                'body' => <<<'MD'
                Staff can now tailor the site's appearance without touching code.

                - Apply a **theme** to set colors, logo, and seal across the whole site
                - A **template editor** for adjusting layout and wording on public pages
                - Preview shipped presets and switch back to a known-good look at any time
                MD,
                'is_published' => true,
            ],
            [
                'version' => '0.4.0',
                'released_on' => '2026-07-20',
                'title' => 'Connected Resident Records And New Optional Modules',
                'summary' => 'A resident record that ties service requests and form submissions together, plus optional modules staff can switch on.',
                'body' => <<<'MD'
                This release links the ways residents reach the town and previews two modules communities can enable when they are ready.

                - A **resident record** that connects a person's service requests and form submissions in one view, so staff have the full history
                - New optional modules that ship **turned off** and are enabled by staff only when needed:
                  - **Online bill payments**, for accepting payments securely by card
                  - **Public records**, for communities that publish arrest and records information
                - Ongoing refinements to the public pages and calendar

                This is the **current alpha release**. Thank you for helping us shape the platform.
                MD,
                'is_published' => true,
            ],
        ];

        foreach ($entries as $entry) {
            ChangelogEntry::updateOrCreate(['version' => $entry['version']], $entry);
        }

        $this->command?->info('Seeded '.count($entries).' changelog entries.');
    }
}
