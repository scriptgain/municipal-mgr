<x-layouts.app :title="$title">
    <x-page-header title="Template Manager" icon="edit"
                   subtitle="Edit the real Blade behind this site. Overrides are stored in the database, so a product update never overwrites your work.">
        <x-slot:actions>
            <x-button href="{{ route('settings.themes.index') }}" variant="secondary" icon="star">Theme Manager</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        <x-card>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                        <x-icon name="file-text" class="w-5 h-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-lg font-semibold text-slate-900 tabular">{{ $templateTotal }}</span>
                        <span class="block text-sm text-slate-500">Editable Templates</span>
                    </span>
                </div>
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-amber-600 ring-1 ring-amber-200">
                        <x-icon name="edit" class="w-5 h-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-lg font-semibold text-slate-900 tabular">{{ $overriddenTotal }}</span>
                        <span class="block text-sm text-slate-500">Currently Overridden</span>
                    </span>
                </div>
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-600 ring-1 ring-emerald-200">
                        <x-icon name="shield" class="w-5 h-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-900">Validated Before Save</span>
                        <span class="block text-sm text-slate-500">A template that does not parse is never stored.</span>
                    </span>
                </div>
            </div>
        </x-card>

        <div class="section-divider"></div>

        <x-card flush>
            <div class="border-b border-slate-100 px-4 py-3">
                <form method="GET" action="{{ route('settings.templates.index') }}" class="flex items-center gap-2">
                    <div class="relative min-w-0 flex-1 max-w-md">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <x-icon name="search" class="w-4 h-4" />
                        </span>
                        <x-input name="q" value="{{ $search }}" class="pl-9" placeholder="Search Templates By Name…" />
                    </div>
                    <x-button type="submit" variant="secondary" size="sm" icon="search">Search</x-button>
                    @if ($search !== '')
                        <x-button href="{{ route('settings.templates.index') }}" variant="ghost" size="sm" icon="x">Clear</x-button>
                    @endif
                </form>
            </div>

            @if (count($groups))
                {{-- Tabs rather than one long scroll: there are well over a
                     hundred templates and nobody should wheel past the ones
                     they do not care about. --}}
                <x-tabs :tabs="$tabs" class="px-4 pb-4 pt-1">
                    @foreach ($groups as $id => $group)
                        <x-tab-panel :name="$id">
                            <p class="mb-3 text-sm text-slate-500">{{ $group['description'] }}</p>
                            <x-table>
                                <thead>
                                    <tr>
                                        <th class="w-1/2">Template</th>
                                        <th class="w-1/4">Status</th>
                                        <th class="w-1/4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['items'] as $item)
                                        <tr>
                                            <td>
                                                <div class="flex items-start gap-2.5 min-w-0">
                                                    <x-icon name="file-text" class="w-4 h-4 mt-0.5 shrink-0 {{ $item['overridden'] ? 'text-amber-500' : 'text-slate-400' }}" />
                                                    <span class="min-w-0">
                                                        <a href="{{ route('settings.templates.edit', $item['view']) }}"
                                                           class="block truncate font-medium text-slate-900 hover:text-brand-700">{{ $item['label'] }}</a>
                                                        <span class="block truncate text-xs text-slate-400" data-tip="{{ $item['path'] }}">{{ $item['view'] }}</span>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($item['overridden'])
                                                    <x-badge color="warn" dot>Overridden</x-badge>
                                                @else
                                                    <x-badge color="neutral" dot>Shipped Default</x-badge>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <x-button :href="route('settings.templates.edit', $item['view'])" variant="secondary" size="sm" icon="edit">Edit</x-button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-table>
                        </x-tab-panel>
                    @endforeach
                </x-tabs>
            @else
                <x-empty-state icon="search" title="No Templates Match That Search"
                               description="Try part of a view name, such as home, news, or layout." />
            @endif
        </x-card>
    </div>
</x-layouts.app>
