<x-layouts.public :title="$event->title">
    <x-site.page-hero :title="$event->title" :eyebrow="$event->category"
                      :crumbs="[['label' => 'Events', 'href' => route('site.events')], ['label' => $event->title]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @if ($event->image_path)
                    <img src="{{ municipal_upload_url($event->image_path) }}" alt=""
                         class="mb-8 aspect-[16/9] w-full rounded-2xl object-cover ring-1 ring-slate-200">
                @endif
                <div class="prose-civic">{!! $event->description !!}</div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Event Details</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">When</dt>
                            <dd class="text-slate-900">{{ $event->whenDisplay() }}</dd>
                        </div>
                        @if ($event->location)
                            <div>
                                <dt class="font-medium text-slate-500">Where</dt>
                                <dd class="text-slate-900">
                                    {{ $event->location }}
                                    @if ($event->address)<span class="block text-slate-600">{{ $event->address }}</span>@endif
                                </dd>
                            </div>
                        @endif
                        @if ($event->department)
                            <div>
                                <dt class="font-medium text-slate-500">Hosted By</dt>
                                <dd><a href="{{ route('site.departments.show', $event->department->slug) }}" class="text-brand-700 hover:underline">{{ $event->department->name }}</a></dd>
                            </div>
                        @endif
                    </dl>

                    @if ($event->registration_url)
                        <a href="{{ $event->registration_url }}" target="_blank" rel="noopener"
                           class="mt-6 flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">
                            Register For This Event <x-icon name="external" class="w-4 h-4" />
                        </a>
                    @endif
                </div>
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
