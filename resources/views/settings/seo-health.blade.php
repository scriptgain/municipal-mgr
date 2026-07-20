<x-layouts.app title="SEO Health">
    <x-page-header title="SEO Health" icon="shield"
                   subtitle="What residents will and will not find when they search for this municipality.">
        <x-slot:actions>
            <x-button variant="secondary" icon="settings" :href="route('settings.seo.edit')">SEO Settings</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($discouraged)
        <x-alert type="warn" class="mb-6" title="This Site Is Hidden From Search Engines">
            The site-wide discourage switch is on, so nothing below is reachable by Google regardless of
            its individual settings. Turn it off under SEO Settings before launch.
        </x-alert>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-stat label="Indexable Records" :value="number_format($summary['indexable'])" icon="globe" />
        <x-stat label="URLs In Sitemap" :value="number_format($sitemapTotal)" icon="database" />
        <x-stat label="Needs Attention" :value="number_format($summary['problems'])" icon="warning" />
        <x-stat label="Hidden From Search" :value="number_format($summary['hidden'])" icon="shield" />
    </div>

    @if ($summary['problems'] === 0)
        <x-alert type="success" class="mb-6" title="Nothing Needs Fixing">
            Every public record has a usable title and a description of a sensible length, and no two
            share a title.
        </x-alert>
    @endif

    {{-- Tabs rather than seven stacked lists: each check is its own job, and a
         site with a few hundred records would otherwise be one long scroll. --}}
    <x-tabs :tabs="[
        'missing' => ['label' => 'Missing Descriptions', 'icon' => 'warning', 'count' => count($groups['missing']['rows'])],
        'duplicates' => ['label' => 'Duplicate Titles', 'icon' => 'copy', 'count' => count($groups['duplicates']['rows'])],
        'long' => ['label' => 'Too Long', 'icon' => 'edit', 'count' => count($groups['long']['rows'])],
        'short' => ['label' => 'Too Short', 'icon' => 'edit', 'count' => count($groups['short']['rows'])],
        'titles' => ['label' => 'Long Titles', 'icon' => 'edit', 'count' => count($groups['titles']['rows'])],
        'no_custom_title' => ['label' => 'No Custom Title', 'icon' => 'info', 'count' => count($groups['no_custom_title']['rows'])],
        'hidden' => ['label' => 'Hidden', 'icon' => 'shield', 'count' => count($groups['hidden']['rows'])],
    ]">
        @foreach ($groups as $key => $group)
            <x-tab-panel :name="$key">
                <x-card :title="$group['label']" :subtitle="$group['help']" flush>
                    @if (count($group['rows']))
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Record</th>
                                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Type</th>
                                        <th scope="col" class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Detail</th>
                                        <th scope="col" class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($group['rows'] as $row)
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="px-5 py-3">
                                                <span class="inline-flex items-center gap-2">
                                                    <span @class([
                                                        'h-2 w-2 shrink-0 rounded-full',
                                                        'bg-rose-500' => $group['tone'] === 'critical',
                                                        'bg-amber-500' => $group['tone'] === 'warning',
                                                        'bg-slate-300' => $group['tone'] === 'info',
                                                    ])></span>
                                                    <span class="font-medium text-slate-900">{{ $row['title'] }}</span>
                                                </span>
                                            </td>
                                            <td class="px-5 py-3">
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $row['type'] }}</span>
                                            </td>
                                            <td class="px-5 py-3 text-slate-600">{{ $row['detail'] }}</td>
                                            <td class="px-5 py-3">
                                                <div class="flex items-center justify-end gap-2">
                                                    @if ($row['public_url'])
                                                        <a href="{{ $row['public_url'] }}" target="_blank" rel="noopener"
                                                           data-tip="Open the public page in a new tab"
                                                           class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50">
                                                            <x-icon name="external" class="w-3.5 h-3.5" /> View
                                                        </a>
                                                    @endif
                                                    @if ($row['edit_url'])
                                                        <a href="{{ $row['edit_url'] }}"
                                                           class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-50">
                                                            <x-icon name="edit" class="w-3.5 h-3.5" /> Fix
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-5 sm:p-6">
                            <x-empty-state icon="check-circle" title="Nothing Here"
                                           description="No records match this check." />
                        </div>
                    @endif
                </x-card>
            </x-tab-panel>
        @endforeach
    </x-tabs>
</x-layouts.app>
