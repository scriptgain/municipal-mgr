<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\DocumentCategory;
use App\Models\FormDefinition;
use App\Models\MenuItem;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Post-install bootstrap (the installer's BOOTSTRAP_CMD).
 *
 * Seeds the structures a municipality always needs — document categories, the
 * primary menu, homepage quick links, a contact form — so a fresh install has
 * a working, navigable site before staff type anything. Idempotent: every
 * write is firstOrCreate, so re-running after an upgrade is safe.
 */
class MunicipalBootstrap extends Command
{
    protected $signature = 'municipal:bootstrap {--force-demo : Also seed demonstration content}';

    protected $description = 'Seed the default menus, document categories, and forms for a new MunicipalMGR install.';

    public function handle(): int
    {
        $categories = [
            ['Ordinances', 'Adopted ordinances and the municipal code.', 'book'],
            ['Resolutions', 'Resolutions adopted by the governing body.', 'book'],
            ['Budgets And Finance', 'Annual budgets, audits, and financial reports.', 'database'],
            ['Agendas And Minutes', 'Meeting agendas, packets, and approved minutes.', 'clock'],
            ['Forms And Applications', 'Permits, licenses, and application forms.', 'edit'],
            ['Plans And Studies', 'General plans, zoning maps, and engineering studies.', 'globe'],
            ['Public Records', 'Records request forms and published records.', 'folder'],
        ];
        foreach ($categories as $i => [$name, $desc, $icon]) {
            DocumentCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $desc, 'icon' => $icon, 'sort_order' => $i]
            );
        }
        $this->info('Document categories ready (' . count($categories) . ').');

        $primary = [
            ['Government', route('site.government'), 'building'],
            ['Departments', route('site.departments'), 'users'],
            ['Meetings', route('site.meetings'), 'clock'],
            ['Documents', route('site.documents'), 'folder'],
            ['News', route('site.news'), 'book'],
            ['Events', route('site.calendar'), 'clock'],
            ['Contact', route('site.contact'), 'globe'],
        ];
        foreach ($primary as $i => [$label, $url, $icon]) {
            MenuItem::firstOrCreate(
                ['menu' => 'primary', 'label' => $label],
                ['url' => $url, 'icon' => $icon, 'sort_order' => $i]
            );
        }

        $quick = [
            ['Pay A Bill', '#', 'database', 'Utilities, permits, and fines.'],
            ['Report An Issue', route('site.report'), 'bolt', 'Potholes, streetlights, and more.'],
            ['Meeting Agendas', route('site.meetings'), 'clock', 'Agendas, packets, and minutes.'],
            ['Apply For A Permit', route('site.documents'), 'edit', 'Building, business, and events.'],
            ['Job Openings', route('site.jobs'), 'users', 'Work for the municipality.'],
            ['Public Notices', route('site.notices'), 'bell', 'Hearings and legal notices.'],
        ];
        foreach ($quick as $i => [$label, $url, $icon, $desc]) {
            MenuItem::firstOrCreate(
                ['menu' => 'quicklinks', 'label' => $label],
                ['url' => $url, 'icon' => $icon, 'description' => $desc, 'sort_order' => $i]
            );
        }

        $footer = [
            ['Accessibility', route('site.accessibility')],
            ['Public Records Request', route('site.documents')],
            ['Staff Directory', route('site.directory')],
            ['Bids And RFPs', route('site.bids')],
            ['Track A Request', route('site.track')],
        ];
        foreach ($footer as $i => [$label, $url]) {
            MenuItem::firstOrCreate(['menu' => 'footer', 'label' => $label], ['url' => $url, 'sort_order' => $i]);
        }
        $this->info('Menus ready (primary, quick links, footer).');

        FormDefinition::firstOrCreate(
            ['slug' => 'contact-us'],
            [
                'name' => 'Contact Us',
                'description' => 'Send a message to Village Hall. We respond within two business days.',
                'success_message' => 'Thank You. Your Message Has Been Sent To Village Hall.',
                'fields' => [
                    ['key' => 'your_name', 'label' => 'Your Name', 'type' => 'text', 'required' => true, 'options' => [], 'help' => null],
                    ['key' => 'email_address', 'label' => 'Email Address', 'type' => 'email', 'required' => true, 'options' => [], 'help' => null],
                    ['key' => 'phone_number', 'label' => 'Phone Number', 'type' => 'tel', 'required' => false, 'options' => [], 'help' => null],
                    ['key' => 'subject', 'label' => 'Subject', 'type' => 'text', 'required' => true, 'options' => [], 'help' => null],
                    ['key' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true, 'options' => [], 'help' => null],
                ],
            ]
        );
        $this->info('Default contact form ready.');

        // A dormant welcome banner: present so staff can see how alerts look,
        // inactive so a fresh site does not shout at its first visitor.
        Alert::firstOrCreate(
            ['title' => 'Welcome To Our New Website'],
            [
                'message' => 'Explore services, meeting agendas, and department contacts. Report an issue any time.',
                'level' => 'info',
                'is_active' => false,
            ]
        );

        foreach ([
            'timezone' => 'America/Phoenix',
            'date_format' => 'M j, Y',
            'time_format' => 'g:i A',
            'rows_per_page' => '25',
            'audit_log_days' => '365',
        ] as $key => $value) {
            if (Setting::get($key) === null) {
                Setting::put($key, $value);
            }
        }
        $this->info('Default settings applied.');

        if ($this->option('force-demo')) {
            $this->call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
        }

        $this->newLine();
        $this->info('MunicipalMGR bootstrap complete. Finish setup at /admin/setup.');

        return self::SUCCESS;
    }
}
