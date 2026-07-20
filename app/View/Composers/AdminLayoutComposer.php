<?php

namespace App\View\Composers;

use App\Models\FormSubmission;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Supplies everything the admin layout renders.
 *
 * The layout template is markup only — no @php blocks, no queries, no
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
                'active' => request()->routeIs('bids.*', 'jobs.*'),
                'items' => [
                    ['Bids And RFPs', route('bids.index'), 'database', request()->routeIs('bids.*')],
                    ['Job Postings', route('jobs.index'), 'users', request()->routeIs('jobs.*')],
                ],
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
                'active' => request()->routeIs('service-requests.*', 'forms.*', 'submissions.*', 'menus.*'),
                'items' => [
                    ['Service Requests', route('service-requests.index'), 'bolt', request()->routeIs('service-requests.*')],
                    ['Forms Builder', route('forms.index'), 'edit', request()->routeIs('forms.*', 'submissions.*')],
                    ['Navigation Menus', route('menus.index'), 'globe', request()->routeIs('menus.*')],
                ],
            ],
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
            'jobs' => ['Job Postings', 'jobs.index'],
            'constituents' => ['Residents', 'constituents.index'],
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
