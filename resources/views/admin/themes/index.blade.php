<x-layouts.app :title="$title">
    <x-page-header title="Theme Manager" icon="star"
                   subtitle="Named looks for this site. Preview one before you commit to it, and hand it to another install as a file.">
        <x-slot:actions>
            <x-button href="{{ route('settings.templates.index') }}" variant="ghost" icon="edit">Template Manager</x-button>
            <x-button type="button" variant="secondary" icon="upload" x-data x-on:click="$dispatch('open-modal', 'import-theme')">Import</x-button>
            <x-button :href="route('settings.themes.create')" icon="plus">New Theme</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        {{-- Swatch cards: a theme is a visual thing, so lead with the colours. --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($themes as $theme)
                <div @class([
                    'flex flex-col rounded-xl bg-white ring-1 shadow-sm overflow-hidden',
                    'ring-brand-300 ring-2' => $theme->is_active,
                    'ring-slate-200' => ! $theme->is_active,
                ])>
                    <div class="h-20 w-full" style="background: linear-gradient(120deg, {{ $theme->token('chrome') }} 0%, {{ $theme->token('brand') }} 100%)"></div>
                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-slate-900">{{ $theme->name }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500 line-clamp-2">{{ $theme->description }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                @if ($theme->is_active)
                                    <x-badge color="success" dot>Active</x-badge>
                                @endif
                                @if ($theme->is_preset)
                                    <x-badge color="neutral">Preset</x-badge>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span class="h-5 w-5 rounded-full ring-1 ring-slate-200" style="background: {{ $theme->token('brand') }}" data-tip="Brand"></span>
                            <span class="h-5 w-5 rounded-full ring-1 ring-slate-200" style="background: {{ $theme->token('accent') }}" data-tip="Accent"></span>
                            <span class="h-5 w-5 rounded-full ring-1 ring-slate-200" style="background: {{ $theme->token('chrome') }}" data-tip="Chrome"></span>
                            <span class="h-5 w-5 rounded-full ring-1 ring-slate-200" style="background: {{ $theme->token('chrome_soft') }}" data-tip="Chrome Soft"></span>
                        </div>

                        <div class="mt-auto flex flex-wrap items-center gap-2 pt-1">
                            @if (! $theme->is_active)
                                <x-confirm-action
                                    :name="'activate-theme-' . $theme->id"
                                    :action="route('settings.themes.activate', $theme)"
                                    title="Activate &quot;{{ $theme->name }}&quot;?"
                                    message="Every visitor will see this look immediately. You can switch back at any time."
                                    confirm="Activate"
                                    confirmIcon="check">
                                    <x-button variant="primary" size="sm" icon="check">Activate</x-button>
                                </x-confirm-action>
                            @endif
                            <x-button :href="$theme->previewUrl()" target="_blank" rel="noopener"
                                      variant="secondary" size="sm" icon="eye">Preview</x-button>
                            @if ($theme->isEditable())
                                <x-button :href="route('settings.themes.edit', $theme)" variant="secondary" size="sm" icon="edit">Edit</x-button>
                            @endif
                            <x-dropdown align="right">
                                <x-slot:trigger>
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 ring-1 ring-inset ring-slate-300 hover:bg-slate-50" aria-label="More Actions">
                                        <x-icon name="dots" class="w-4 h-4" />
                                    </button>
                                </x-slot:trigger>
                                <form method="POST" action="{{ route('settings.themes.duplicate', $theme) }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                                        <x-icon name="copy" class="w-4 h-4 shrink-0 text-slate-400" /> Duplicate
                                    </button>
                                </form>
                                <x-dropdown-item icon="download" href="{{ route('settings.themes.export', $theme) }}">Export As JSON</x-dropdown-item>
                                @if ($theme->isDeletable())
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <x-confirm-action
                                        :name="'delete-theme-' . $theme->id"
                                        :action="route('settings.themes.destroy', $theme)"
                                        method="DELETE"
                                        tone="danger"
                                        title="Delete &quot;{{ $theme->name }}&quot;?"
                                        message="This removes the theme permanently. Export it first if you might want it back."
                                        confirm="Delete Theme"
                                        confirmIcon="trash"
                                        confirmVariant="danger">
                                        <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">
                                            <x-icon name="trash" class="w-4 h-4 shrink-0" /> Delete
                                        </button>
                                    </x-confirm-action>
                                @endif
                            </x-dropdown>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="section-divider"></div>

        {{-- Table view with massSelect, matching every other admin list. --}}
        <x-card flush title="All Themes" subtitle="Presets and the active theme are protected from bulk delete.">
            <div x-data="{{ bulk_state($themes->pluck('id')) }}">
                <x-bulk-bar :action="route('settings.themes.bulk-destroy')" label="Theme" modal="bulk-delete-theme" />
                <x-table>
                    <thead>
                        <tr>
                            <th class="w-12"><x-select-all /></th>
                            <th>Theme</th>
                            <th class="w-32">Status</th>
                            <th class="w-40">Created By</th>
                            <th class="w-40">Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($themes as $theme)
                            <tr>
                                <td><x-select-row :id="$theme->id" :label="$theme->name" /></td>
                                <td>
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="h-6 w-6 shrink-0 rounded-md ring-1 ring-slate-200" style="background: {{ $theme->token('brand') }}"></span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium text-slate-900">{{ $theme->name }}</span>
                                            <span class="block truncate text-xs text-slate-400">{{ $theme->slug }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if ($theme->is_active)
                                        <x-status-dot color="success" label="Active" />
                                    @elseif ($theme->is_preset)
                                        <x-status-dot color="neutral" label="Preset" />
                                    @else
                                        <x-status-dot color="info" label="Custom" />
                                    @endif
                                </td>
                                <td class="truncate">{{ $theme->author?->name ?? 'Shipped' }}</td>
                                <td class="whitespace-nowrap text-slate-500">{{ $theme->updated_at?->format(config('municipal.date_format')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>
        </x-card>
    </div>

    <x-modal name="import-theme" title="Import A Theme" icon="upload"
             subtitle="Paste a theme export, or upload the JSON file another install gave you.">
        <form method="POST" action="{{ route('settings.themes.import') }}" enctype="multipart/form-data" id="import-theme-form" class="space-y-4">
            @csrf
            <x-field label="Theme File" for="theme-file" hint="A .json file exported from a MunicipalMGR install.">
                <input type="file" name="file" id="theme-file" accept="application/json,.json"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
            </x-field>
            <div class="flex items-center gap-3">
                <span class="h-px flex-1 bg-slate-200"></span>
                <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Or Paste</span>
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>
            <x-field label="Theme JSON" for="theme-json">
                <textarea name="json" id="theme-json" rows="6" spellcheck="false"
                          class="mm-code-area block w-full rounded-lg border-0 bg-white px-3 py-2 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500"
                          placeholder='{"format":"municipalmgr.theme", ... }'></textarea>
            </x-field>
        </form>
        <x-slot:footer>
            <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'import-theme')">Cancel</x-button>
            <x-button size="sm" icon="upload" x-on:click="document.getElementById('import-theme-form').submit()">Import Theme</x-button>
        </x-slot:footer>
    </x-modal>
</x-layouts.app>
