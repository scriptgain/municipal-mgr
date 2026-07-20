<x-layouts.app :title="$title">
    <x-page-header :title="'Compare Version #' . $version->id" icon="filter"
                   :subtitle="$label . ' · ' . $view">
        <x-slot:actions>
            <x-button :href="route('settings.templates.edit', $view)" variant="ghost" size="sm" icon="chevron-left">Back To Editor</x-button>
            <x-confirm-action
                :name="'revert-' . $version->id"
                :action="route('settings.templates.revert', [$view, $version->id])"
                title="Revert To Version #{{ $version->id }}?"
                message="The current template is snapshotted first, so this is reversible. The version is re-checked before it is applied."
                confirm="Revert"
                confirmIcon="restore">
                <x-button variant="primary" size="sm" icon="restore">Revert To This Version</x-button>
            </x-confirm-action>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        <x-card>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <span class="inline-flex items-center gap-2">
                    <span class="text-slate-500">Saved</span>
                    <span class="font-medium text-slate-800">{{ $version->created_at?->format(config('municipal.date_format') . ' ' . config('municipal.time_format')) }}</span>
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="text-slate-500">By</span>
                    <span class="font-medium text-slate-800">{{ $version->user?->name ?? 'System' }}</span>
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="text-slate-500">Action</span>
                    <x-badge color="neutral" dot>{{ $version->actionLabel() }}</x-badge>
                </span>
                @if ($version->note)
                    <span class="inline-flex items-center gap-2 min-w-0">
                        <span class="text-slate-500">Note</span>
                        <span class="truncate font-medium text-slate-800">{{ $version->note }}</span>
                    </span>
                @endif
            </div>
        </x-card>

        <div class="section-divider"></div>

        <x-card flush title="Version #{{ $version->id }} Compared With What Is Live Now"
                subtitle="Red lines exist only in the old version. Green lines exist only in what is live now.">
            <x-slot:actions>
                <x-badge color="success">+{{ $diff['added'] }}</x-badge>
                <x-badge color="danger">-{{ $diff['removed'] }}</x-badge>
            </x-slot:actions>

            <div class="mm-diff">
                @foreach ($diff['rows'] as $row)
                    <div class="mm-diff-row mm-diff-{{ $row['type'] }}">
                        <span class="mm-diff-num">{{ $row['left'] ?? '' }}</span>
                        <span class="mm-diff-num">{{ $row['right'] ?? '' }}</span>
                        <span class="mm-diff-sign">{{ $row['type'] === 'add' ? '+' : ($row['type'] === 'remove' ? '-' : ' ') }}</span>
                        <span class="mm-diff-text">{{ $row['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
</x-layouts.app>
