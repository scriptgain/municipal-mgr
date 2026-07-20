<x-layouts.public :title="$document->title">
    <x-site.page-hero :title="$document->title" :eyebrow="$document->category?->name"
                      :crumbs="[['label' => 'Documents', 'href' => route('site.documents')], ['label' => $document->title]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @if ($document->description)
                    <div class="prose-civic">{!! nl2br(e($document->description)) !!}</div>
                @endif

                <a href="{{ route('site.documents.download', $document->slug) }}"
                   class="mt-8 flex items-center gap-4 rounded-2xl bg-brand-700 p-6 text-white transition hover:bg-brand-800">
                    <x-icon name="download" class="w-8 h-8 shrink-0" />
                    <span class="min-w-0">
                        <span class="block text-lg font-semibold">Download This Document</span>
                        <span class="block truncate text-sm text-brand-100">{{ $document->file_name }} &middot; {{ $document->sizeDisplay() }}</span>
                    </span>
                </a>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Document Details</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        @if ($document->reference)
                            <div>
                                <dt class="font-medium text-slate-500">Reference</dt>
                                <dd class="text-slate-900">{{ $document->reference }}</dd>
                            </div>
                        @endif
                        @if ($document->document_date)
                            <div>
                                <dt class="font-medium text-slate-500">Document Date</dt>
                                <dd class="text-slate-900">{{ $document->document_date->format(config('municipal.date_format')) }}</dd>
                            </div>
                        @endif
                        @if ($document->department)
                            <div>
                                <dt class="font-medium text-slate-500">Department</dt>
                                <dd><a href="{{ route('site.departments.show', $document->department->slug) }}" class="text-brand-700 hover:underline">{{ $document->department->name }}</a></dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium text-slate-500">File</dt>
                            <dd class="text-slate-900">{{ $document->extension() }} &middot; {{ $document->sizeDisplay() }}</dd>
                        </div>
                    </dl>
                </div>

                @if ($related->count())
                    <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <h2 class="font-semibold text-slate-900">Related Documents</h2>
                        <span class="seal-rule mt-3 mb-4"></span>
                        <ul class="space-y-3 text-sm">
                            @foreach ($related as $item)
                                <li><a href="{{ route('site.documents.show', $item->slug) }}" class="text-brand-700 hover:underline">{{ $item->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
