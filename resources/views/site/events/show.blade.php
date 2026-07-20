<x-layouts.public :title="$event->title">
    <x-site.page-hero :title="$event->title" :eyebrow="$event->category"
                      :crumbs="[['label' => 'Events', 'href' => route('site.events')], ['label' => $event->title]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                {{-- Date block plus status: the two questions a resident actually
                     arrives with, answered before any scrolling. --}}
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex shrink-0 overflow-hidden rounded-2xl ring-1 ring-slate-200">
                        <div class="flex w-20 flex-col items-center justify-center bg-brand-800 py-3 text-white">
                            <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-seal-300">{{ $event->starts_at->format('M') }}</span>
                            <span class="font-display text-3xl font-semibold leading-none">{{ $event->starts_at->format('j') }}</span>
                            <span class="mt-0.5 text-[11px] text-brand-200">{{ $event->starts_at->format('Y') }}</span>
                        </div>
                        <div class="flex flex-col justify-center bg-white px-5 py-3">
                            <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $event->starts_at->format('l') }}</span>
                            <span class="mt-0.5 font-medium text-slate-900">{{ $event->whenDisplay() }}</span>
                        </div>
                    </div>

                    <span @class([
                        'inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 ring-inset',
                        'bg-emerald-50 text-emerald-700 ring-emerald-200' => $event->statusBadge()[1] === 'emerald',
                        'bg-seal-100 text-brand-900 ring-seal-300' => $event->statusBadge()[1] === 'seal',
                        'bg-brand-50 text-brand-800 ring-brand-200' => $event->statusBadge()[1] === 'brand',
                        'bg-slate-100 text-slate-600 ring-slate-300' => $event->statusBadge()[1] === 'slate',
                    ])>
                        <span @class([
                            'h-1.5 w-1.5 rounded-full',
                            'bg-emerald-500' => $event->statusBadge()[1] === 'emerald',
                            'bg-seal-500' => $event->statusBadge()[1] === 'seal',
                            'bg-brand-600' => $event->statusBadge()[1] === 'brand',
                            'bg-slate-400' => $event->statusBadge()[1] === 'slate',
                        ])></span>
                        {{ $event->statusBadge()[0] }}
                    </span>
                </div>

                @if ($event->image_path)
                    <img src="{{ municipal_upload_url($event->image_path) }}" alt=""
                         class="mt-8 aspect-[16/9] w-full rounded-2xl object-cover ring-1 ring-slate-200">
                @endif

                @if ($event->hasDescription())
                    <div class="prose-civic mt-8">{!! $event->description !!}</div>
                @else
                    <p class="mt-8 text-slate-500">No further details have been published for this event. Contact the town office if you need more information.</p>
                @endif

                <div class="section-divider mt-10 pt-6">
                    <a href="{{ route('site.events') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 transition hover:text-brand-800">
                        <x-icon name="chevron-left" class="w-4 h-4 shrink-0" />
                        Back To All Events
                    </a>
                </div>
            </div>

            <aside class="min-w-0 space-y-6 lg:sticky lg:top-28 lg:self-start">
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Event Details</h2>
                    <span class="seal-rule mt-3 mb-5"></span>

                    <dl class="space-y-5 text-sm">
                        <div class="flex gap-3">
                            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                                <x-icon name="calendar" class="w-4 h-4" />
                            </span>
                            <div class="min-w-0">
                                <dt class="font-medium text-slate-500">When</dt>
                                <dd class="text-slate-900">{{ $event->whenDisplay() }}</dd>
                            </div>
                        </div>

                        @if ($event->location)
                            <div class="flex gap-3">
                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                                    <x-icon name="map-pin" class="w-4 h-4" />
                                </span>
                                <div class="min-w-0">
                                    <dt class="font-medium text-slate-500">Where</dt>
                                    <dd class="text-slate-900">
                                        {{ $event->location }}
                                        @if ($event->address)<span class="block text-slate-600">{{ $event->address }}</span>@endif
                                    </dd>
                                    @if ($event->mapsUrl())
                                        <a href="{{ $event->mapsUrl() }}" target="_blank" rel="noopener"
                                           class="mt-1 inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 transition hover:text-brand-800">
                                            <x-icon name="external" class="w-3.5 h-3.5 shrink-0" />
                                            Get Directions
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($event->department)
                            <div class="flex gap-3">
                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                                    <x-icon name="building" class="w-4 h-4" />
                                </span>
                                <div class="min-w-0">
                                    <dt class="font-medium text-slate-500">Hosted By</dt>
                                    <dd><a href="{{ route('site.departments.show', $event->department->slug) }}" class="font-medium text-brand-700 hover:underline">{{ $event->department->name }}</a></dd>
                                </div>
                            </div>
                        @endif

                        @if ($event->category)
                            <div class="flex gap-3">
                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                                    <x-icon name="star" class="w-4 h-4" />
                                </span>
                                <div class="min-w-0">
                                    <dt class="font-medium text-slate-500">Category</dt>
                                    <dd class="text-slate-900">{{ $event->category }}</dd>
                                </div>
                            </div>
                        @endif
                    </dl>

                    <div class="section-divider mt-6 space-y-2 pt-6">
                        @if ($event->registration_url)
                            <a href="{{ $event->registration_url }}" target="_blank" rel="noopener"
                               class="flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">
                                Register For This Event
                                <x-icon name="external" class="w-4 h-4 shrink-0" />
                            </a>
                        @endif

                        {{-- Calendar picker. Inline x-data only, so it is immune
                             to the Alpine.data registration ordering trap. --}}
                        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true"
                                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-brand-800 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-50">
                                <x-icon name="calendar" class="w-4 h-4 shrink-0" />
                                Add To Calendar
                                <x-icon name="chevron-down" class="w-4 h-4 shrink-0 text-slate-400 transition-transform" ::class="open && 'rotate-180'" />
                            </button>

                            <div x-show="open" x-cloak x-transition @click="open = false"
                                 class="absolute inset-x-0 bottom-full z-30 mb-2 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-slate-200">
                                <a href="{{ $event->googleCalendarUrl() }}" target="_blank" rel="noopener"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-800">
                                    <x-icon name="calendar" class="w-4 h-4 shrink-0 text-slate-400" />
                                    Google Calendar
                                </a>
                                <a href="{{ $event->outlookCalendarUrl() }}" target="_blank" rel="noopener"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-800">
                                    <x-icon name="envelope" class="w-4 h-4 shrink-0 text-slate-400" />
                                    Outlook
                                </a>
                                <a href="{{ $event->yahooCalendarUrl() }}" target="_blank" rel="noopener"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-800">
                                    <x-icon name="star" class="w-4 h-4 shrink-0 text-slate-400" />
                                    Yahoo Calendar
                                </a>
                                <a href="{{ $event->icsDataUri() }}" download="{{ $event->icsFilename() }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-800">
                                    <x-icon name="download" class="w-4 h-4 shrink-0 text-slate-400" />
                                    Apple Calendar Or Outlook Desktop
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
