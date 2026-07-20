<x-layouts.public :title="$file->title">
    <x-site.page-hero :title="$file->title" :eyebrow="$file->folder?->name" :crumbs="$crumbs" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                @if ($file->description)
                    <div class="prose-civic">{!! nl2br(e($file->description)) !!}</div>
                @endif

                @if ($file->isImage())
                    <figure class="mt-8">
                        <img src="{{ $file->url() }}" alt="{{ $file->alt_text }}"
                             class="w-full rounded-2xl object-contain ring-1 ring-slate-200">
                        @if ($file->alt_text)
                            <figcaption class="mt-2 text-sm text-slate-500">{{ $file->alt_text }}</figcaption>
                        @endif
                    </figure>
                @endif

                <a href="{{ route('site.files.download', $file) }}"
                   class="mt-8 flex items-center gap-4 rounded-2xl bg-brand-700 p-6 text-white transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    <x-icon name="download" class="w-8 h-8 shrink-0" />
                    <span class="min-w-0">
                        <span class="block text-lg font-semibold">Download This File</span>
                        <span class="block truncate text-sm text-brand-100">{{ $file->file_name }} &middot; {{ $file->sizeDisplay() }}</span>
                    </span>
                </a>
            </div>

            <aside class="space-y-6" aria-label="File Details">
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">File Details</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        @if ($file->reference)
                            <div>
                                <dt class="font-medium text-slate-500">Reference</dt>
                                <dd class="text-slate-900">{{ $file->reference }}</dd>
                            </div>
                        @endif
                        @if ($file->document_date)
                            <div>
                                <dt class="font-medium text-slate-500">Document Date</dt>
                                <dd class="text-slate-900">{{ $file->document_date->format(config('municipal.date_format')) }}</dd>
                            </div>
                        @endif
                        @if ($file->folder)
                            <div>
                                <dt class="font-medium text-slate-500">Folder</dt>
                                <dd><a href="{{ route('site.files', ['folder' => $file->folder->slug]) }}" class="rounded text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">{{ $file->folder->name }}</a></dd>
                            </div>
                        @endif
                        @if ($file->department)
                            <div>
                                <dt class="font-medium text-slate-500">Department</dt>
                                <dd><a href="{{ route('site.departments.show', $file->department->slug) }}" class="rounded text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">{{ $file->department->name }}</a></dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium text-slate-500">File</dt>
                            <dd class="text-slate-900">{{ $file->extension() }} &middot; {{ $file->sizeDisplay() }}</dd>
                        </div>
                    </dl>
                </div>

                @if ($related->count())
                    <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <h2 class="font-semibold text-slate-900">Related Files</h2>
                        <span class="seal-rule mt-3 mb-4"></span>
                        <ul class="space-y-3 text-sm">
                            @foreach ($related as $item)
                                <li><a href="{{ route('site.files.show', $item) }}" class="rounded text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">{{ $item->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
