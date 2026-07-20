<?php

namespace App\View\Composers;

use App\Models\FormSubmission;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Supplies everything the admin layout renders.
 *
 * The layout template is markup only, no @php blocks, no queries, no
 * conditionals beyond simple presence checks. Navigation structure, the active
 * trail, breadcrumbs, and badge counts are all computed here.
 */
class AdminLayoutComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        $view->with([
            'navGroups' => $this->nav(),
            'activeGroupItems' => $this->activeGroupItems(),
            'crumbs' => $this->crumbs($view),
            'userInitials' => $user?->initials() ?? 'MM',
            'userName' => $user?->name ?? 'Staff',
            'userEmail' => $user?->email,
            'userRoleLabel' => $user?->roleLabel(),
            'userIsAdmin' => (bool) $user?->isAdmin(),
            'openRequestCount' => $this->openRequests(),
            'unreadSubmissionCount' => $this->unreadSubmissions(),
            'shellMaxWidth' => config('municipal.max_width', 'max-w-7xl'),
            // Theme assets. Resolved here rather than in the layout so the
            // template stays markup only and both layouts ask the same service.
            'themeLogoUrl' => rescue(fn () => app(\App\Services\Themes\ThemeService::class)->logoUrl(), null, false),
            'themeFaviconUrl' => rescue(fn () => app(\App\Services\Themes\ThemeService::class)->faviconUrl(), null, false),
        ]);
    }

    /** Top-level navigation. Groups collapse into dropdowns; links stand alone. */
    private function nav(): array
    {
        return [
            [
                'type' => 'link',
                'label' => 'Dashboard',
                'href' => route('dashboard'),
                'icon' => 'dashboard',
                'active' => request()->routeIs('dashboard'),
            ],
            [
                'type' => 'group',
                'label' => 'Content',
                'icon' => 'edit',
                'active' => request()->routeIs('pages.*', 'news.*', 'notices.*', 'events.*', 'alerts.*'),
                'items' => [
                    ['Pages', route('pages.index'), 'book', request()->routeIs('pages.*')],
                    ['News And Announcements', route('news.index'), 'bell', request()->routeIs('news.*')],
                    ['Public Notices', route('notices.index'), 'warning', request()->routeIs('notices.*')],
                    ['Events Calendar', route('events.index'), 'clock', request()->routeIs('events.*')],
                    ['Alerts Banner', route('alerts.index'), 'bolt', request()->routeIs('alerts.*')],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'Government',
                'icon' => 'building',
                'active' => request()->routeIs('departments.*', 'staff.*', 'officials.*', 'meetings.*'),
                'items' => [
                    ['Departments', route('departments.index'), 'building', request()->routeIs('departments.*')],
                    ['Staff Directory', route('staff.index'), 'users', request()->routeIs('staff.*')],
                    ['Elected Officials', route('officials.index'), 'shield', request()->routeIs('officials.*')],
                    ['Meetings', route('meetings.index'), 'clock', request()->routeIs('meetings.*')],
                ],
            ],
            [
                'type' => 'link',
                'label' => 'File Manager',
                'href' => route('files.index'),
                'icon' => 'folder',
                'active' => request()->routeIs('files.*', 'folders.*'),
            ],
            [
                'type' => 'group',
                'label' => 'Records',
                'icon' => 'archive',
                'active' => request()->routeIs('bids.*', 'jobs.*', 'arrest-records.*', 'settings.records.*'),
                'items' => array_merge([
                    ['Bids And RFPs', route('bids.index'), 'database', request()->routeIs('bids.*')],
                    ['Job Postings', route('jobs.index'), 'users', request()->routeIs('jobs.*')],
                ], $this->arrestRecordItems()),
            ],
            [
                'type' => 'link',
                'label' => 'Residents',
                'href' => route('constituents.index'),
                'icon' => 'users',
                'active' => request()->routeIs('constituents.*'),
            ],
            [
                'type' => 'group',
                'label' => 'Services',
                'icon' => 'bolt',
                'active' => request()->routeIs('service-requests.*', 'forms.*', 'submissions.*', 'menus.*', 'bills.*', 'bill-types.*', 'payments.*'),
                'items' => array_merge([
                    ['Service Requests', route('service-requests.index'), 'bolt', request()->routeIs('service-requests.*')],
                    ['Forms Builder', route('forms.index'), 'edit', request()->routeIs('forms.*', 'submissions.*')],
                    ['Navigation Menus', route('menus.index'), 'globe', request()->routeIs('menus.*')],
                ], $this->paymentItems()),
            ],
        ];
    }

    /**
     * Jail And Arrest Records nav, which is conditional in a way nothing else
     * in this panel is.
     *
     * While the module is disabled the ONLY thing that appears is the settings
     * screen that enables it: no blotter, no roster, no hint in the sidebar
     * that an install is one click away from publishing arrest data.
     */
    private function arrestRecordItems(): array
    {
        $settingsItem = ['Arrest Records Settings', route('settings.records.edit'), 'settings', request()->routeIs('settings.records.*')];

        if (! rescue(fn () => \App\Services\RecordsSettings::enabled(), false, false)) {
            return [$settingsItem];
        }

        return [
            ['Arrest Records', route('arrest-records.index'), 'shield', request()->routeIs('arrest-records.index', 'arrest-records.create', 'arrest-records.edit')],
            ['Inmate Roster', route('arrest-records.roster'), 'lock', request()->routeIs('arrest-records.roster')],
            ['Expungement Log', route('arrest-records.expungements'), 'book', request()->routeIs('arrest-records.expungements')],
            $settingsItem,
        ];
    }

    /**
     * Pay Your Bill nav, which is conditional in the same way Arrest Records is.
     *
     * While the module is disabled NOTHING appears here at all, not even a
     * pointer to its settings screen: that screen lives under Settings, which
     * is where an operator goes looking to switch a module on. Adding a
     * top-level item was deliberately avoided, since the primary nav was just
     * regrouped down to three dropdowns to cut the item count.
     */
    private function paymentItems(): array
    {
        if (! rescue(fn () => \App\Services\Payments\PaymentSettings::isEnabled(), false, false)) {
            return [];
        }

        return [
            ['Bills', route('bills.index'), 'database', request()->routeIs('bills.*')],
            ['Payments Received', route('payments.index'), 'scale', request()->routeIs('payments.index', 'payments.show')],
            ['Reconciliation', route('payments.reconciliation'), 'archive', request()->routeIs('payments.reconciliation')],
            ['Bill Types', route('bill-types.index'), 'clipboard', request()->routeIs('bill-types.*')],
        ];
    }

    /** Items of the currently active group, for the sticky left menu. */
    private function activeGroupItems(): ?array
    {
        foreach ($this->nav() as $item) {
            if (($item['type'] ?? '') === 'group' && ($item['active'] ?? false)) {
                return $item['items'];
            }
        }

        return null;
    }

    /** Breadcrumb trail derived from the route name and the page title. */
    private function crumbs(View $view): array
    {
        $routeName = request()->route()?->getName() ?? '';
        $section = strtok($routeName, '.');
        $title = $view->getData()['title'] ?? null;

        $map = [
            'pages' => ['Pages', 'pages.index'],
            'news' => ['News And Announcements', 'news.index'],
            'notices' => ['Public Notices', 'notices.index'],
            'events' => ['Events Calendar', 'events.index'],
            'alerts' => ['Alerts Banner', 'alerts.index'],
            'departments' => ['Departments', 'departments.index'],
            'staff' => ['Staff Directory', 'staff.index'],
            'officials' => ['Elected Officials', 'officials.index'],
            'meetings' => ['Meetings', 'meetings.index'],
            'files' => ['File Manager', 'files.index'],
            'folders' => ['File Manager', 'files.index'],
            'bids' => ['Bids And RFPs', 'bids.index'],
            'arrest-records' => ['Arrest Records', 'arrest-records.index'],
            'jobs' => ['Job Postings', 'jobs.index'],
            'constituents' => ['Residents', 'constituents.index'],
            'bills' => ['Bills', 'bills.index'],
            'bill-types' => ['Bill Types', 'bill-types.index'],
            'payments' => ['Payments Received', 'payments.index'],
            'service-requests' => ['Service Requests', 'service-requests.index'],
            'forms' => ['Forms Builder', 'forms.index'],
            'submissions' => ['Form Submissions', 'forms.index'],
            'menus' => ['Navigation Menus', 'menus.index'],
            'settings' => ['Settings', 'settings.index'],
        ];

        if (! isset($map[$section]) || ! Route::has($map[$section][1])) {
            return [];
        }

        [$label, $indexRoute] = $map[$section];
        $isIndex = $routeName === $indexRoute;

        $crumbs = [['label' => $label, 'href' => $isIndex ? null : route($indexRoute)]];
        if (! $isIndex && $title && $title !== $label) {
            $crumbs[] = ['label' => $title, 'href' => null];
        }

        return $crumbs;
    }

    private function openRequests(): int
    {
        return rescue(fn () => ServiceRequest::open()->count(), 0, false);
    }

    private function unreadSubmissions(): int
    {
        return rescue(fn () => FormSubmission::whereNull('read_at')->count(), 0, false);
    }
}
