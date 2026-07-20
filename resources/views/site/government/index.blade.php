<x-layouts.public title="Your Government">
    <x-site.page-hero title="Your Government"
                      subtitle="The elected officials who represent you, and how to reach them."
                      :crumbs="[['label' => 'Government']]" />

    <x-site.section :divider="false">
        @if ($officials->count())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($officials as $official)
                    <article class="flex h-full flex-col items-start rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm">
                        <x-avatar size="xl" :initials="$official->initials()" :name="$official->name"
                                  :src="$official->photo_path ? municipal_upload_url($official->photo_path) : null" />
                        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-seal-700">{{ $official->office }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-slate-900">
                            <a href="{{ route('site.government.show', $official) }}" class="hover:text-brand-700 hover:underline">{{ $official->name }}</a>
                        </h2>
                        @if ($official->district)
                            <p class="text-sm text-slate-500">{{ $official->district }}</p>
                        @endif
                        @if ($official->termDisplay())
                            <p class="mt-2 text-xs text-slate-400">{{ $official->termDisplay() }}</p>
                        @endif
                        <div class="mt-4 space-y-1 text-sm">
                            @if ($official->email)
                                <a href="mailto:{{ $official->email }}" class="block break-all text-brand-700 hover:underline">{{ $official->email }}</a>
                            @endif
                            @if ($official->phone)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $official->phone) }}" class="block tabular text-slate-600 hover:text-brand-700">{{ $official->phone }}</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <x-site.empty title="No Officials Listed" icon="shield" />
        @endif
    </x-site.section>

    @if ($meetings->count())
        <x-site.section tone="muted" title="Upcoming Public Meetings"
                        subtitle="All meetings are open to the public." :href="route('site.meetings')" linkLabel="All Meetings">
            <ul class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 divide-y divide-slate-100">
                @foreach ($meetings as $meeting)
                    <li class="flex flex-wrap items-center gap-4 p-5">
                        <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-50 text-brand-800 ring-1 ring-brand-200">
                            <span class="text-xs font-semibold uppercase">{{ $meeting->meets_at->format('M') }}</span>
                            <span class="text-xl font-bold leading-none tabular">{{ $meeting->meets_at->format('j') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('site.meetings.show', $meeting->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700 hover:underline">{{ $meeting->displayTitle() }}</a>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $meeting->meets_at->format(config('municipal.time_format')) }} @if ($meeting->location) &middot; {{ $meeting->location }} @endif</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-site.section>
    @endif

    @if ($former->count())
        <x-site.section title="Former Officials" subtitle="A historical record of who has served.">
            <ul class="flex flex-wrap gap-3">
                @foreach ($former as $official)
                    <li class="rounded-full bg-slate-100 px-4 py-2 text-sm text-slate-700">
                        {{ $official->name }} — {{ $official->office }}
                        @if ($official->term_end)<span class="text-slate-400">(to {{ $official->term_end->format('Y') }})</span>@endif
                    </li>
                @endforeach
            </ul>
        </x-site.section>
    @endif
</x-layouts.public>
