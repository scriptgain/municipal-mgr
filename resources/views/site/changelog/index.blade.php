<x-layouts.public title="What's New">
    <x-site.page-hero title="What's New"
                      eyebrow="Release Notes"
                      icon="megaphone"
                      subtitle="A running record of improvements to this website and the services behind it."
                      :crumbs="[['label' => 'What\'s New']]" />

    <x-site.section :divider="false">
        {{-- Alpha notice: this platform is in active development. --}}
        <div role="note" class="mb-10 flex flex-col gap-3 rounded-2xl bg-amber-50 p-5 ring-1 ring-inset ring-amber-200 sm:flex-row sm:items-start sm:gap-4">
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-300">
                <x-icon name="bolt" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <p class="flex items-center gap-2 text-sm font-semibold text-amber-900">
                    <span class="inline-flex items-center rounded-full bg-amber-600 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">Alpha</span>
                    This Platform Is In Active Alpha Development
                </p>
                <p class="mt-1.5 text-sm leading-relaxed text-amber-800">
                    Features listed below are shipping quickly and may change as we refine them. We are publishing
                    release notes openly so you can follow along with what is new.
                </p>
            </div>
        </div>

        @if ($entries->count())
            <ol class="relative space-y-10 border-l border-slate-200 pl-6 sm:pl-8">
                @foreach ($entries as $entry)
                    <li class="relative">
                        {{-- Timeline node --}}
                        <span aria-hidden="true"
                              class="absolute -left-[1.6rem] top-1.5 flex h-3.5 w-3.5 items-center justify-center rounded-full ring-4 ring-white sm:-left-[2.1rem] {{ $loop->first ? 'bg-brand-600' : 'bg-slate-300' }}"></span>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200">
                                v{{ $entry->version }}
                            </span>
                            @if ($loop->first)
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-300">
                                    Current Alpha
                                </span>
                            @endif
                            <time datetime="{{ $entry->released_on?->format('Y-m-d') }}" class="text-sm text-slate-500">
                                {{ $entry->released_on?->format(config('municipal.date_format')) }}
                            </time>
                        </div>

                        <h2 class="mt-3 font-display text-xl font-semibold tracking-tight text-slate-900">{{ $entry->title }}</h2>
                        @if ($entry->summary)
                            <p class="mt-1.5 text-base leading-relaxed text-slate-600">{{ $entry->summary }}</p>
                        @endif

                        @if ($entry->renderedBody())
                            <div class="prose-civic mt-4 max-w-none">{!! $entry->renderedBody() !!}</div>
                        @endif
                    </li>
                @endforeach
            </ol>
        @else
            <x-site.empty title="No Release Notes Yet" icon="megaphone"
                          message="Release notes will appear here as new features ship." />
        @endif
    </x-site.section>
</x-layouts.public>
