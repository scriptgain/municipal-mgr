<x-layouts.app title="Media Library">
    <x-page-header title="Media Library" icon="folder" subtitle="Images and files used across the public site.">
    </x-page-header>

    <x-card title="Upload Files" subtitle="Images up to 20 MB. Add alt text after uploading — it is required for accessibility.">
        <form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
            @csrf
            <x-field label="Choose Files" for="files" class="flex-1 min-w-[16rem]" :error="$errors->first('files.0')">
                <input id="files" name="files[]" type="file" multiple required accept="image/*,.pdf"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
            </x-field>
            <x-button type="submit" icon="upload">Upload</x-button>
        </form>
    </x-card>

    <div class="section-divider my-8"></div>

    <x-card flush>
        <div x-data="{{ bulk_state($records->pluck('id')) }}">
            <x-admin.index-toolbar :search="$search" placeholder="Search Media…" />
            <x-bulk-bar :action="route('media.bulk-destroy')" label="File" modal="bulk-delete-media" />

            @if ($records->count())
                <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($records as $item)
                        <figure class="overflow-hidden rounded-xl ring-1 ring-slate-200 bg-white">
                            <div class="relative aspect-[4/3] bg-slate-50">
                                @if ($item->isImage())
                                    <img src="{{ $item->url() }}" alt="{{ $item->alt_text ?: $item->name }}" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full items-center justify-center text-slate-400">
                                        <x-icon name="file-text" class="w-10 h-10" />
                                    </span>
                                @endif
                                <span class="absolute left-2 top-2"><x-select-row :id="$item->id" :label="$item->name" /></span>
                                @if (! $item->alt_text && $item->isImage())
                                    <span class="absolute right-2 top-2" data-tip="This image has no alt text, which is an accessibility problem on a government site">
                                        <x-badge color="warn">No Alt Text</x-badge>
                                    </span>
                                @endif
                            </div>
                            <figcaption class="p-3">
                                <form method="POST" action="{{ route('media.update', $item) }}" class="space-y-2">
                                    @csrf @method('PUT')
                                    <label class="sr-only" for="name-{{ $item->id }}">File Name</label>
                                    <input id="name-{{ $item->id }}" name="name" value="{{ $item->name }}"
                                           class="block w-full rounded-lg border-0 py-1.5 px-2 text-sm font-medium text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-600">
                                    <label class="sr-only" for="alt-{{ $item->id }}">Alt Text</label>
                                    <input id="alt-{{ $item->id }}" name="alt_text" value="{{ $item->alt_text }}" placeholder="Describe this image"
                                           class="block w-full rounded-lg border-0 py-1.5 px-2 text-xs text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-600">
                                    <div class="flex items-center justify-between gap-2 pt-1">
                                        <span class="text-xs text-slate-400 tabular">{{ $item->sizeDisplay() }}</span>
                                        <div class="flex items-center gap-1">
                                            <button type="button" data-copy="{{ $item->url() }}"
                                                    class="rounded-lg px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50 transition">Copy URL</button>
                                            <x-button type="submit" size="sm" variant="secondary">Save</x-button>
                                        </div>
                                    </div>
                                </form>
                                <div class="mt-2 flex justify-end">
                                    <x-delete-button :name="'del-media-' . $item->id" :action="route('media.destroy', $item)"
                                                     title="Delete This File?"
                                                     message="Any page still using this file will show a broken image." />
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            @else
                <x-admin.empty title="No Media Uploaded" icon="folder"
                               message="Upload photographs, seals, and downloadable images for use across the site." />
            @endif
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
