<x-layouts.app :title="$title">
    <x-page-header :title="$label" icon="edit" :subtitle="$view">
        <x-slot:actions>
            <x-button href="{{ route('settings.templates.index') }}" variant="ghost" size="sm" icon="chevron-left">All Templates</x-button>
            @if ($override)
                <x-confirm-action
                    name="reset-template"
                    :action="route('settings.templates.reset', $view)"
                    method="DELETE"
                    tone="danger"
                    title="Reset To The Shipped Default?"
                    message="This removes your override completely and the site goes back to the template that shipped with the product. The current content is saved to history first, so this can be undone."
                    confirm="Reset To Default"
                    confirmIcon="restore"
                    confirmVariant="danger">
                    <x-button variant="secondary" size="sm" icon="restore">Reset To Default</x-button>
                </x-confirm-action>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Validation failure from the last save attempt. Shown before anything
         else, because the operator's template was NOT stored. --}}
    @if (session('template_error'))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-rose-600 ring-1 ring-rose-200">
                    <x-icon name="warning" class="w-5 h-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-rose-900">Not Saved: This Template Does Not Compile</h3>
                    <p class="mt-1 text-sm text-rose-800 break-words">{{ session('template_error')['message'] }}</p>
                    @if (session('template_error')['line'])
                        <p class="mt-1 text-xs font-medium text-rose-700">
                            Failure at line {{ session('template_error')['line'] }}
                            @if (session('template_error')['stage'] === 'php')
                                of the compiled PHP. Blade directives usually keep their line numbers, so look at or just above the same line in your source.
                            @endif
                        </p>
                    @endif
                    @if (count(session('template_error')['snippet'] ?? []))
                        <div class="mt-3 overflow-x-auto rounded-lg bg-rose-950/95 p-3">
                            <pre class="mm-code text-xs leading-relaxed text-rose-100"><code>@foreach (session('template_error')['snippet'] as $line)<span class="{{ ($line['is_error'] ?? false) ? 'block bg-rose-500/30' : 'block' }}"><span class="inline-block w-10 select-none pr-3 text-right text-rose-400/70">{{ $line['line'] }}</span>{{ $line['text'] }}</span>@endforeach</code></pre>
                        </div>
                    @endif
                    <p class="mt-3 text-xs text-rose-700">Your edit is still in the editor below. Nothing on the live site changed.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="space-y-6">
        {{-- Status strip --}}
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 min-w-0">
                    <span class="inline-flex items-center gap-2 text-sm">
                        <span class="text-slate-500">Status</span>
                        @if ($override)
                            <x-badge color="warn" dot>Overridden</x-badge>
                        @else
                            <x-badge color="neutral" dot>Shipped Default</x-badge>
                        @endif
                    </span>
                    <span class="inline-flex items-center gap-2 text-sm min-w-0">
                        <span class="text-slate-500">Shipped File</span>
                        <span class="truncate font-mono text-xs text-slate-700" data-tip="This file is never modified. Your override shadows it.">{{ $path }}</span>
                    </span>
                    @if ($override && $override->editor)
                        <span class="inline-flex items-center gap-2 text-sm">
                            <span class="text-slate-500">Last Edited By</span>
                            <span class="font-medium text-slate-800">{{ $override->editor->name }}</span>
                        </span>
                    @endif
                </div>
                @if ($previewUrl)
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">
                        <x-icon name="external" class="w-4 h-4 shrink-0" /> Open The Live Page
                    </a>
                @endif
            </div>
        </x-card>

        {{-- Active preview banner --}}
        @if (session()->has('template_preview'))
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-600 ring-1 ring-amber-200">
                            <x-icon name="eye" class="w-5 h-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-amber-900">Preview Active</p>
                            <p class="text-sm text-amber-800">You are seeing an unsaved draft on the live site. No other visitor is. It expires after 10 minutes.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($previewUrl)
                            <x-button :href="$previewUrl" target="_blank" rel="noopener" variant="secondary" size="sm" icon="external">View It</x-button>
                        @endif
                        <form method="POST" action="{{ route('settings.templates.preview.stop') }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm" icon="x">Stop Preview</x-button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="section-divider"></div>

        <x-tabs :tabs="[
            'editor' => ['label' => 'Editor', 'icon' => 'edit'],
            'history' => ['label' => 'Version History', 'icon' => 'clock', 'count' => $versions->count()],
            'shipped' => ['label' => 'Shipped Default', 'icon' => 'file-text'],
            'help' => ['label' => 'Blade Reference', 'icon' => 'book'],
        ]">
            {{-- ---------------- Editor ---------------- --}}
            <x-tab-panel name="editor">
                <form method="POST" action="{{ route('settings.templates.update', $view) }}" id="template-form" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="mm-editor" data-template-editor
                         data-check-url="{{ route('settings.templates.check', $view) }}"
                         data-compiled-url="{{ route('settings.templates.compiled', $view) }}">
                        <div class="mm-editor-bar">
                            <div class="mm-editor-meta">
                                <span class="mm-editor-file">{{ $view }}</span>
                                <span class="mm-editor-stat" data-editor-stat>{{ $lineCount }} lines</span>
                            </div>
                            <div class="mm-editor-status" data-editor-status data-state="idle">
                                <span class="mm-editor-dot"></span>
                                <span data-editor-status-text>Not Checked Yet</span>
                            </div>
                        </div>
                        <div class="mm-editor-body">
                            <div class="mm-gutter" data-editor-gutter aria-hidden="true"></div>
                            <textarea name="content" id="template-content" class="mm-code-area" spellcheck="false"
                                      autocapitalize="off" autocomplete="off" autocorrect="off"
                                      aria-label="Blade Template Source">{{ $content }}</textarea>
                        </div>
                    </div>

                    <x-field label="Change Note" for="template-note" hint="Optional. Shown in the version history so the next person knows why.">
                        <x-input name="note" id="template-note" maxlength="255" placeholder="e.g. Added the county seal to the footer" />
                    </x-field>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-button type="submit" icon="check">Save Template</x-button>
                            <x-button type="button" variant="secondary" icon="shield" data-editor-check>Check Syntax</x-button>
                            <x-button type="button" variant="secondary" icon="eye" data-editor-preview>Preview On The Live Site</x-button>
                            <x-button type="button" variant="ghost" icon="file-text" data-editor-compiled>View Compiled PHP</x-button>
                        </div>
                        <p class="text-xs text-slate-500">Saving runs the same check. An invalid template is refused, not stored.</p>
                    </div>
                </form>

                {{-- Preview posts the editor's current content, so it is a
                     separate form filled in by public/js/templates.js. --}}
                <form method="POST" action="{{ route('settings.templates.preview', $view) }}" id="template-preview-form" class="hidden">
                    @csrf
                    <input type="hidden" name="content" data-preview-content>
                </form>

                <x-modal name="compiled-php" title="Compiled PHP" icon="file-text"
                         subtitle="What Blade turns your template into. This is what the syntax check parses." maxWidth="max-w-4xl">
                    <div class="overflow-x-auto rounded-lg bg-slate-950 p-3">
                        <pre class="mm-code text-xs leading-relaxed text-slate-100"><code data-compiled-output>Loading…</code></pre>
                    </div>
                </x-modal>
            </x-tab-panel>

            {{-- ---------------- History ---------------- --}}
            <x-tab-panel name="history">
                @if ($versions->count())
                    <x-card flush>
                        <x-table>
                            <thead>
                                <tr>
                                    <th class="w-16">Version</th>
                                    <th class="w-32">Action</th>
                                    <th>Note</th>
                                    <th class="w-40">By</th>
                                    <th class="w-40">When</th>
                                    <th class="w-48 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($versions as $version)
                                    <tr>
                                        <td class="font-medium text-slate-900">#{{ $version->id }}</td>
                                        <td>
                                            @if ($version->action === 'reset')
                                                <x-badge color="danger" dot>{{ $version->actionLabel() }}</x-badge>
                                            @elseif ($version->action === 'revert')
                                                <x-badge color="info" dot>{{ $version->actionLabel() }}</x-badge>
                                            @else
                                                <x-badge color="neutral" dot>{{ $version->actionLabel() }}</x-badge>
                                            @endif
                                        </td>
                                        <td class="truncate" data-tip="{{ $version->note }}">{{ $version->note ?: 'No Note' }}</td>
                                        <td class="truncate">{{ $version->user?->name ?? 'System' }}</td>
                                        <td class="whitespace-nowrap text-slate-500">{{ $version->created_at?->format(config('municipal.date_format') . ' ' . config('municipal.time_format')) }}</td>
                                        <td>
                                            <div class="flex items-center justify-end gap-2">
                                                <x-button :href="route('settings.templates.diff', [$view, $version->id])" variant="ghost" size="sm" icon="filter">Compare</x-button>
                                                <x-confirm-action
                                                    :name="'revert-' . $version->id"
                                                    :action="route('settings.templates.revert', [$view, $version->id])"
                                                    title="Revert To Version #{{ $version->id }}?"
                                                    message="The current template is snapshotted first, so this is reversible. The version is re-checked before it is applied."
                                                    confirm="Revert"
                                                    confirmIcon="restore">
                                                    <x-button variant="secondary" size="sm" icon="restore">Revert</x-button>
                                                </x-confirm-action>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-table>
                    </x-card>
                @else
                    <x-card>
                        <x-empty-state icon="clock" title="No History Yet"
                                       description="Every save, revert, and reset of this template is recorded here, with a one-click way back to any of them." />
                    </x-card>
                @endif
            </x-tab-panel>

            {{-- ---------------- Shipped default ---------------- --}}
            <x-tab-panel name="shipped">
                <x-card :title="'The Template That Shipped With The Product'"
                        subtitle="Read only. This is what Reset To Default puts back.">
                    @if ($shippedDiff)
                        <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
                            <x-badge color="success">+{{ $shippedDiff['added'] }} Added</x-badge>
                            <x-badge color="danger">-{{ $shippedDiff['removed'] }} Removed</x-badge>
                            <span class="text-slate-500">Your override compared with the shipped file.</span>
                        </div>
                    @endif
                    <div class="overflow-x-auto rounded-lg bg-slate-950 p-3">
                        <pre class="mm-code text-xs leading-relaxed text-slate-100"><code>{{ $shipped }}</code></pre>
                    </div>
                </x-card>
            </x-tab-panel>

            {{-- ---------------- Reference ---------------- --}}
            <x-tab-panel name="help">
                <div class="grid gap-6 lg:grid-cols-2">
                    <x-card title="Blade In One Screen" subtitle="The directives you will use most.">
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-start gap-3">
                                <code class="mm-chip">&#123;&#123; $variable &#125;&#125;</code>
                                <dd class="min-w-0 text-slate-600">Print a value, escaped. Safe by default.</dd>
                            </div>
                            <div class="flex items-start gap-3">
                                <code class="mm-chip">&#123;!! $html !!&#125;</code>
                                <dd class="min-w-0 text-slate-600">Print raw HTML. Only for content you trust.</dd>
                            </div>
                            <div class="flex items-start gap-3">
                                <code class="mm-chip">&#64;if / &#64;else / &#64;endif</code>
                                <dd class="min-w-0 text-slate-600">Show something conditionally.</dd>
                            </div>
                            <div class="flex items-start gap-3">
                                <code class="mm-chip">&#64;foreach / &#64;endforeach</code>
                                <dd class="min-w-0 text-slate-600">Loop over a collection.</dd>
                            </div>
                            <div class="flex items-start gap-3">
                                <code class="mm-chip">&lt;x-card&gt;</code>
                                <dd class="min-w-0 text-slate-600">Use a shared component. Every component in Shared Components is available.</dd>
                            </div>
                            <div class="flex items-start gap-3">
                                <code class="mm-chip">&#123;&#123;-- comment --&#125;&#125;</code>
                                <dd class="min-w-0 text-slate-600">A comment that never reaches the browser.</dd>
                            </div>
                        </dl>
                    </x-card>

                    <x-card title="House Rules" subtitle="Follow these and your edits will survive updates.">
                        <ul class="space-y-3 text-sm text-slate-600">
                            <li class="flex items-start gap-2.5">
                                <x-icon name="warning" class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" />
                                <span>Never start line 1 with <code class="mm-chip">&#64;php</code>. Blade mis-parses it and the page fails. The editor refuses to save it.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <x-icon name="warning" class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" />
                                <span>Keep logic out of templates. If you need a calculation, it belongs in a composer or component class, not here.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <x-icon name="check-circle" class="w-4 h-4 mt-0.5 shrink-0 text-emerald-500" />
                                <span>The shipped file is never modified. Reset To Default always gets you back.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <x-icon name="check-circle" class="w-4 h-4 mt-0.5 shrink-0 text-emerald-500" />
                                <span>Every save is versioned. You can revert to any earlier version in one click.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <x-icon name="info" class="w-4 h-4 mt-0.5 shrink-0 text-brand-500" />
                                <span>Press Tab inside the editor to indent rather than jump to the next field.</span>
                            </li>
                        </ul>
                    </x-card>
                </div>
            </x-tab-panel>
        </x-tabs>
    </div>

    {{-- Plain DOM, deliberately not an Alpine.data() component: this file is
         loaded AFTER the Alpine bundle, and Alpine fires alpine:init on start,
         so a late Alpine.data() call would register nothing at all. --}}
    <script defer src="{{ asset_v('js/templates.js') }}"></script>
</x-layouts.app>
