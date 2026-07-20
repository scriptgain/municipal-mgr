<x-layouts.app :title="$file->title">
    <x-page-header :title="$file->title" icon="file-text"
                   :subtitle="$file->file_name . ' · ' . $file->sizeDisplay() . ' · ' . number_format($file->download_count) . ' Download(s)'">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left"
                      :href="route('files.index', $file->folder ? ['folder' => $file->folder->slug] : [])">Back To Files</x-button>
            <x-button variant="secondary" icon="external" :href="route('site.files.show', $file)" target="_blank" rel="noopener">Preview</x-button>
            {{-- Delete lives here, outside the update <form>: this control ships
                 its own POST form and forms cannot be nested. --}}
            <x-delete-button :name="'del-file-' . $file->id" :action="route('files.destroy', $file)"
                             title="Delete This File?"
                             message="The file is removed from disk and any page or notice linking to it will show a not-found page. This cannot be undone." />
        </x-slot:actions>
    </x-page-header>

    <div class="section-divider mb-6"></div>

    <form method="POST" action="{{ route('files.update', $file) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                {{-- Tabs rather than one long form: details, publishing, and the
                     file itself are three separate jobs. --}}
                <x-tabs :tabs="[
                    'details' => ['label' => 'Details', 'icon' => 'edit'],
                    'publishing' => ['label' => 'Publishing', 'icon' => 'globe'],
                    'file' => ['label' => 'The File', 'icon' => 'upload'],
                ]">
                    <x-tab-panel name="details">
                        <x-card>
                            <div class="space-y-5">
                                <x-field label="Title" for="title" required :error="$errors->first('title')"
                                         hint="What residents see in the file browser and in search results.">
                                    <x-input id="title" name="title" required maxlength="200" value="{{ old('title', $file->title) }}" />
                                </x-field>

                                <x-field label="Description" for="description" :error="$errors->first('description')">
                                    <textarea id="description" name="description" rows="4"
                                              class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('description', $file->description) }}</textarea>
                                </x-field>

                                <div class="grid gap-5 sm:grid-cols-2">
                                    <x-field label="Reference Number" for="reference" hint="Ordinance 2026-14, Resolution 88, and so on.">
                                        <x-input id="reference" name="reference" maxlength="120" value="{{ old('reference', $file->reference) }}" />
                                    </x-field>

                                    <x-field label="Document Date" for="document_date" hint="The date on the record, not the upload date.">
                                        <x-input id="document_date" name="document_date" type="date"
                                                 value="{{ old('document_date', $file->document_date?->format('Y-m-d')) }}" />
                                    </x-field>
                                </div>

                                <x-field label="Search Keywords" for="keywords"
                                         hint="Extra words residents might search for. Separate them with commas.">
                                    <x-input id="keywords" name="keywords" maxlength="255" value="{{ old('keywords', $file->keywords) }}" />
                                </x-field>

                                @if ($file->isImage())
                                    <div class="section-divider"></div>
                                    <x-field label="Alt Text" for="alt_text" :error="$errors->first('alt_text')"
                                             hint="Describe the image for screen-reader users. Required for accessibility on a government site.">
                                        <x-input id="alt_text" name="alt_text" maxlength="255" value="{{ old('alt_text', $file->alt_text) }}" />
                                    </x-field>
                                @endif
                            </div>
                        </x-card>
                    </x-tab-panel>

                    <x-tab-panel name="publishing">
                        <x-card>
                            <div class="space-y-6">
                                <x-field label="Folder" for="folder_id" :error="$errors->first('folder_id')"
                                         hint="Moving a file between folders never changes its public URL.">
                                    <x-select id="folder_id" name="folder_id">
                                        <option value="">Unfiled (No Folder)</option>
                                        @foreach ($folderTree as $node)
                                            <option value="{{ $node['folder']->id }}" @selected(old('folder_id', $file->folder_id) == $node['folder']->id)>{{ $node['prefix'] }}{{ $node['folder']->name }}</option>
                                        @endforeach
                                    </x-select>
                                </x-field>

                                <x-field label="Owning Department" for="department_id" :error="$errors->first('department_id')"
                                         hint="Department editors may only manage files owned by their own department.">
                                    <x-select id="department_id" name="department_id">
                                        <option value="">No Department</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}" @selected(old('department_id', $file->department_id) == $department->id)>{{ $department->name }}</option>
                                        @endforeach
                                    </x-select>
                                </x-field>

                                <div class="section-divider"></div>

                                <x-toggle name="is_published" :checked="(bool) old('is_published', $file->is_published)"
                                          label="Published"
                                          description="Unpublished Files Are Hidden And Cannot Be Downloaded By The Public." />

                                <x-toggle name="public_visibility" :checked="$file->isPublic()"
                                          label="Visible To The Public"
                                          description="Turn Off To Make This A Staff Only File." />
                            </div>
                        </x-card>

                        {{-- Search Appearance sits with Publishing rather than in a
                             tab of its own: whether a document is findable is the
                             same decision as whether it is published. --}}
                        <div class="mt-6">
                            <x-admin.seo-panel :record="$file" kind="File" />
                        </div>
                    </x-tab-panel>

                    <x-tab-panel name="file">
                        <x-card title="Replace This File"
                                subtitle="Upload a revision. The public URL stays the same, so existing links keep working; the old copy is removed from disk.">
                            <x-field label="Replacement File" for="replacement" :error="$errors->first('replacement')">
                                <input id="replacement" name="replacement" type="file"
                                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                            </x-field>
                        </x-card>
                    </x-tab-panel>
                </x-tabs>
            </div>

            <aside class="space-y-6" aria-label="File Summary">
                <x-card title="Preview">
                    @if ($file->isImage())
                        <img src="{{ $file->url() }}" alt="{{ $file->alt_text }}"
                             class="w-full rounded-xl object-contain ring-1 ring-slate-200">
                    @else
                        <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <span class="inline-flex h-11 items-center rounded-lg bg-white px-3 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $file->extension() }}</span>
                            <span class="min-w-0 text-sm">
                                <span class="block truncate font-medium text-slate-900">{{ $file->file_name }}</span>
                                <span class="block text-slate-500">{{ $file->sizeDisplay() }}</span>
                            </span>
                        </div>
                    @endif

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Downloads</dt>
                            <dd class="tabular font-medium text-slate-900">{{ number_format($file->download_count) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Type</dt>
                            <dd class="font-medium text-slate-900">{{ $file->extension() }}</dd>
                        </div>
                        @if ($file->width)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">Dimensions</dt>
                                <dd class="tabular font-medium text-slate-900">{{ $file->width }} x {{ $file->height }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Uploaded</dt>
                            <dd class="font-medium text-slate-900">{{ $file->created_at?->format(config('municipal.date_format')) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4">
                        <label for="direct-url" class="block text-xs font-medium text-slate-600">Direct URL</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input id="direct-url" type="text" readonly value="{{ $file->url() }}"
                                   class="block w-full rounded-lg border-0 bg-slate-50 px-3 py-2 text-xs text-slate-700 ring-1 ring-inset ring-slate-200">
                            <button type="button" data-copy="{{ $file->url() }}"
                                    class="shrink-0 rounded-lg px-2.5 py-2 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-50">Copy</button>
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <x-button type="submit" icon="check" class="w-full">Save File</x-button>
                </x-card>
            </aside>
        </div>
    </form>
</x-layouts.app>
