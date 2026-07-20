<x-layouts.public :title="$page->title" :description="$page->meta_description ?: $page->summary">
    <x-site.page-hero :title="$page->title" :subtitle="$page->summary"
                      :eyebrow="$page->department?->name"
                      :crumbs="collect($trail)->map(fn ($p) => ['label' => $p->title, 'href' => $p->is($page) ? null : route('site.page', $p->slug)])->all()" />

    <x-site.section :divider="false">
        <div class="grid gap-10 {{ $page->template === 'standard' ? 'lg:grid-cols-3' : '' }}">
            <div class="{{ $page->template === 'standard' ? 'lg:col-span-2' : '' }} space-y-10">
                @forelse ($blocks as $block)
                    <div>
                        @if ($block['heading'])
                            <h2 class="font-display text-2xl font-semibold tracking-tight text-slate-900">{{ $block['heading'] }}</h2>
                            <span class="seal-rule mt-3 mb-5"></span>
                        @endif

                        @if ($block['type'] === 'callout')
                            <div class="rounded-2xl bg-brand-50 p-6 ring-1 ring-brand-200">
                                <div class="prose-civic">{!! $block['body'] !!}</div>
                            </div>
                        @elseif ($block['type'] === 'embed')
                            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200 [&_iframe]:w-full [&_iframe]:aspect-video">
                                {!! $block['body'] !!}
                            </div>
                        @else
                            <div class="prose-civic">{!! $block['body'] !!}</div>
                        @endif
                    </div>
                @empty
                    <x-site.empty title="This Page Has No Content Yet" icon="file-text"
                                  message="Staff can add sections to this page from the panel." />
                @endforelse
            </div>

            @if ($page->template === 'standard')
                <aside class="space-y-6">
                    @if ($page->department)
                        <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                            <h2 class="font-semibold text-slate-900">{{ $page->department->name }}</h2>
                            <span class="seal-rule mt-3 mb-4"></span>
                            <dl class="space-y-3 text-sm">
                                @if ($page->department->phone)
                                    <div>
                                        <dt class="font-medium text-slate-500">Phone</dt>
                                        <dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $page->department->phone) }}" class="text-brand-700 hover:underline">{{ $page->department->phone }}</a></dd>
                                    </div>
                                @endif
                                @if ($page->department->email)
                                    <div>
                                        <dt class="font-medium text-slate-500">Email</dt>
                                        <dd><a href="mailto:{{ $page->department->email }}" class="text-brand-700 hover:underline break-all">{{ $page->department->email }}</a></dd>
                                    </div>
                                @endif
                                @if ($page->department->hours)
                                    <div>
                                        <dt class="font-medium text-slate-500">Hours</dt>
                                        <dd class="text-slate-700">{{ $page->department->hours }}</dd>
                                    </div>
                                @endif
                            </dl>
                            <a href="{{ route('site.departments.show', $page->department->slug) }}"
                               class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">
                                Department Home <x-icon name="chevron-right" class="w-4 h-4" />
                            </a>
                        </div>
                    @endif

                    @if ($page->children->count())
                        <nav class="rounded-2xl bg-white p-6 ring-1 ring-slate-200" aria-label="Related Pages">
                            <h2 class="font-semibold text-slate-900">In This Section</h2>
                            <span class="seal-rule mt-3 mb-4"></span>
                            <ul class="space-y-2 text-sm">
                                @foreach ($page->children as $child)
                                    <li>
                                        <a href="{{ route('site.page', $child->slug) }}" class="text-brand-700 hover:underline">{{ $child->title }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif
                </aside>
            @endif
        </div>
    </x-site.section>
</x-layouts.public>
