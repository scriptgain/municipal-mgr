<?php

namespace App\View\Composers;

use App\Models\Alert;
use App\Models\Department;
use App\Models\MenuItem;
use App\Services\SiteSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Supplies the public site's chrome: identity, navigation, quick links, the
 * live alert banner, and footer contact details.
 *
 * Every public page hits this, so the menu/alert lookups are cached briefly —
 * a municipal homepage on the evening of a storm advisory should not run six
 * queries per visitor.
 */
class PublicLayoutComposer
{
    private const CACHE_SECONDS = 120;

    public function compose(View $view): void
    {
        $site = SiteSettings::all();

        $view->with([
            'site' => $site,
            'siteName' => $site['site_name'],
            'siteFormalName' => SiteSettings::formalName(),
            'primaryNav' => $this->menu('primary'),
            'utilityNav' => $this->menu('utility'),
            'footerNav' => $this->menu('footer'),
            'quickLinks' => $this->menu('quicklinks'),
            'liveAlert' => $this->alert(),
            'footerDepartments' => $this->departments(),
            'maxWidth' => config('municipal.max_width', 'max-w-7xl'),
            'currentYear' => now()->year,
        ]);
    }

    private function menu(string $menu): array
    {
        return Cache::remember("site.menu.{$menu}", self::CACHE_SECONDS, function () use ($menu) {
            return MenuItem::published()->menu($menu)
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->where('is_published', true), 'children.page', 'page'])
                ->get()
                ->map(fn (MenuItem $item) => [
                    'label' => $item->label,
                    'href' => $item->href(),
                    'icon' => $item->icon,
                    'description' => $item->description,
                    'new_tab' => $item->new_tab,
                    'children' => $item->children->map(fn (MenuItem $c) => [
                        'label' => $c->label,
                        'href' => $c->href(),
                        'icon' => $c->icon,
                        'description' => $c->description,
                        'new_tab' => $c->new_tab,
                    ])->all(),
                ])->all();
        });
    }

    /** The single highest-severity live alert, if any. */
    private function alert(): ?array
    {
        return Cache::remember('site.alert', 60, function () {
            $alert = Alert::live()->get()->sortByDesc(fn (Alert $a) => $a->weight())->first();

            return $alert ? [
                'id' => $alert->id,
                'title' => $alert->title,
                'message' => $alert->message,
                'level' => $alert->level,
                'link_url' => $alert->link_url,
                'link_label' => $alert->link_label,
                'dismissible' => $alert->is_dismissible,
            ] : null;
        });
    }

    private function departments(): array
    {
        return Cache::remember('site.footer.departments', self::CACHE_SECONDS, function () {
            return Department::published()->ordered()->limit(8)
                ->get(['name', 'slug'])
                ->map(fn (Department $d) => ['name' => $d->name, 'href' => route('site.departments.show', $d->slug)])
                ->all();
        });
    }
}
