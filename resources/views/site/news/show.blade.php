<x-layouts.public :title="$post->title" :description="$post->teaser(160)">
    <x-site.page-hero :title="$post->title"
                      :eyebrow="$post->category . ' · ' . $post->published_at?->format(config('municipal.date_format'))"
                      :crumbs="[['label' => 'News', 'href' => route('site.news')], ['label' => $post->title]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <article class="lg:col-span-2">
                @if ($post->image_path)
                    <img src="{{ municipal_upload_url($post->image_path) }}" alt=""
                         class="mb-8 aspect-[16/9] w-full rounded-2xl object-cover ring-1 ring-slate-200">
                @endif

                @if ($post->excerpt)
                    <p class="text-xl leading-relaxed text-slate-700">{{ $post->excerpt }}</p>
                    <div class="section-divider my-8"></div>
                @endif

                <div class="prose-civic">{!! $post->body !!}</div>

                <div class="section-divider mt-10 pt-6 text-sm text-slate-500">
                    Posted {{ $post->published_at?->format(config('municipal.date_format')) }}
                    @if ($post->department) by {{ $post->department->name }} @endif
                </div>
            </article>

            <aside>
                @if ($related->count())
                    <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                        <h2 class="font-semibold text-slate-900">More News</h2>
                        <span class="seal-rule mt-3 mb-4"></span>
                        <ul class="space-y-4">
                            @foreach ($related as $item)
                                <li>
                                    <a href="{{ route('site.news.show', $item->slug) }}" class="text-sm font-medium text-brand-700 hover:underline">{{ $item->title }}</a>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $item->published_at?->format(config('municipal.date_format')) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
