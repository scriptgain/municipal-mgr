<x-layouts.app title="File Manager">
    <x-page-header title="File Manager" icon="folder"
                   subtitle="Every public document, form, and image in one place.">
        <x-slot:actions>
            <x-button variant="secondary" icon="external" :href="route('site.files')" target="_blank" rel="noopener">
                View Public Browser
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="section-divider mb-6"></div>

    <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
        {{-- Folder tree. A real <aside> landmark so screen-reader users can jump to it. --}}
        <aside aria-label="Folder Navigation" class="lg:sticky lg:top-6 lg:self-start">
            <x-card title="Folders" subtitle="{{ $counts['all'] }} File(s) Total" padding="p-2">
                <x-admin.folder-tree :tree="$folderTree" :active="$folder?->slug" :rootHref="route('files.index')" />
            </x-card>
        </aside>

        <div class="min-w-0">
            {{-- Tabs keep upload and folder administration off the browse screen
                 instead of stacking them into one long scroll. --}}
            <x-tabs :tabs="[
                'browse' => ['label' => 'Browse Files', 'icon' => 'archive', 'count' => $files->total()],
                'upload' => ['label' => 'Upload Files', 'icon' => 'upload'],
                'folder' => ['label' => 'Manage Folders', 'icon' => 'folder'],
            ]">

                {{-- ---------------------------------------------------------
                     Browse
                --------------------------------------------------------- --}}
                <x-tab-panel name="browse">
                    <nav aria-label="Breadcrumb" class="mb-4 flex flex-wrap items-center gap-1.5 text-sm text-slate-500">
                        <a href="{{ route('files.index') }}" class="rounded font-medium hover:text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">All Files</a>
                        @if ($folder)
                            @foreach ($folder->trail() as $crumb)
                                <x-icon name="chevron-right" class="w-3.5 h-3.5 text-slate-300" />
                                <a href="{{ route('files.index', ['folder' => $crumb->slug]) }}"
                                   @class(['rounded hover:text-brand-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500', 'font-semibold text-slate-900' => $crumb->id === $folder->id])>{{ $crumb->name }}</a>
                            @endforeach
                        @endif
                    </nav>

                    @if ($childFolders->count())
                        <ul class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($childFolders as $child)
                                <li>
                                    <a href="{{ route('files.index', ['folder' => $child->slug]) }}"
                                       class="flex items-center gap-3 rounded-xl bg-white p-3.5 ring-1 ring-slate-200 shadow-sm transition hover:ring-brand-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                                            <x-icon :name="$child->icon ?: 'folder'" class="w-4 h-4" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-slate-900">{{ $child->name }}</span>
                                            @if ($child->description)
                                                <span class="block truncate text-xs text-slate-500">{{ $child->description }}</span>
                                            @endif
                                        </span>
                                        @unless ($child->is_public)
                                            <x-badge color="warn">Staff Only</x-badge>
                                        @endunless
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <x-card flush>
                        <div x-data="{{ file_bulk_state($files->pluck('id')) }}">
                            {{-- Filter toolbar. A real <form> with real labels; the
                                 folder is carried through as a hidden field so
                                 filtering never dumps you back to the root. --}}
                            <form method="GET" role="search" aria-label="Search Files"
                                  class="flex flex-wrap items-end gap-3 border-b border-slate-200 bg-white px-4 py-3">
                                @if ($folder)<input type="hidden" name="folder" value="{{ $folder->slug }}">@endif

                                <div class="flex-1 min-w-[14rem]">
                                    <label for="q" class="block text-xs font-medium text-slate-600">Search</label>
                                    <div class="relative mt-1">
                                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-slate-400" />
                                        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Title, Reference, Or File Name"
                                               class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-600">
                                    </div>
                                </div>

                                <div>
                                    <label for="kind" class="block text-xs font-medium text-slate-600">Type</label>
                                    <select id="kind" name="kind" data-auto-submit
                                            class="mt-1 rounded-lg border-0 py-2 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                                        <option value="">All Types</option>
                                        <option value="document" @selected($activeKind === 'document')>Documents</option>
                                        <option value="image" @selected($activeKind === 'image')>Images</option>
                                        <option value="other" @selected($activeKind === 'other')>Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="visibility" class="block text-xs font-medium text-slate-600">Visibility</label>
                                    <select id="visibility" name="visibility" data-auto-submit
                                            class="mt-1 rounded-lg border-0 py-2 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                                        <option value="">All Visibility</option>
                                        <option value="public" @selected($activeVisibility === 'public')>Public</option>
                                        <option value="staff" @selected($activeVisibility === 'staff')>Staff Only</option>
                                    </select>
                                </div>

                                <x-button type="submit" variant="secondary" size="sm" icon="filter">Apply</x-button>
                                @if ($search || $activeKind || $activeVisibility)
                                    <x-button variant="ghost" size="sm" :href="route('files.index', $folder ? ['folder' => $folder->slug] : [])">Clear</x-button>
                                @endif
                            </form>

                            {{-- massSelect bar: bulk delete and bulk move, both modal-confirmed. --}}
                            <form method="POST" action="{{ route('files.bulk-destroy') }}" x-ref="bulkForm" class="hidden">
                                @csrf @method('DELETE')
                            </form>
                            <form method="POST" action="{{ route('files.bulk-move') }}" x-ref="moveForm" class="hidden">
                                @csrf
                                <input type="hidden" name="folder_id" x-model="moveTarget">
                            </form>

                            <div x-show="selected.length" x-cloak
                                 class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-200 bg-brand-50 px-4 py-2.5">
                                <span class="text-sm font-medium text-brand-800">
                                    <span x-text="selected.length"></span> Selected
                                </span>
                                <div class="flex items-center gap-2">
                                    <x-button type="button" variant="secondary" size="sm" x-on:click="selected = []">Clear Selection</x-button>
                                    <x-button type="button" variant="secondary" size="sm" icon="folder"
                                              x-on:click="$dispatch('open-modal', 'bulk-move-files')">Move To Folder</x-button>
                                    <x-button type="button" variant="danger" size="sm" icon="trash"
                                              x-on:click="$dispatch('open-modal', 'bulk-delete-files')">Delete Selected</x-button>
                                </div>
                            </div>

                            @if ($files->count())
                                <x-table flush>
                                    <thead>
                                        <tr>
                                            <th scope="col" class="w-12"><x-select-all /></th>
                                            <th scope="col">File</th>
                                            <th scope="col" class="w-32">Type</th>
                                            <th scope="col" class="w-48">Folder</th>
                                            <th scope="col" class="w-32">Visibility</th>
                                            <th scope="col" class="w-28 text-right">Downloads</th>
                                            <th scope="col" class="w-24 text-right">Size</th>
                                            <th scope="col" class="w-28"><span class="sr-only">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($files as $file)
                                            <tr>
                                                <td><x-select-row :id="$file->id" :label="$file->title" /></td>
                                                <td>
                                                    <div class="flex items-center gap-3 min-w-0">
                                                        @if ($file->isImage())
                                                            <img src="{{ $file->url() }}" alt="" class="h-9 w-9 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                                                        @else
                                                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                                                                <x-icon name="file-text" class="w-4 h-4" />
                                                            </span>
                                                        @endif
                                                        <span class="min-w-0">
                                                            <a href="{{ route('files.edit', $file) }}" class="block truncate font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $file->title }}</a>
                                                            <span class="block truncate text-xs text-slate-500">
                                                                {{ $file->file_name }}
                                                                @if ($file->reference) &middot; {{ $file->reference }}@endif
                                                            </span>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $file->extension() }}</span>
                                                    @if ($file->isImage() && ! $file->alt_text)
                                                        <span data-tip="This image has no alt text, which is an accessibility problem on a government site">
                                                            <x-badge color="warn">No Alt Text</x-badge>
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="truncate">{{ $file->folder?->name ?? 'Unfiled' }}</td>
                                                <td>
                                                    @if (! $file->is_published)
                                                        <x-badge color="neutral" dot>Unpublished</x-badge>
                                                    @elseif ($file->isPublic())
                                                        <x-badge color="success" dot>Public</x-badge>
                                                    @else
                                                        <x-badge color="warn" dot>Staff Only</x-badge>
                                                    @endif
                                                </td>
                                                <td class="text-right tabular">{{ number_format($file->download_count) }}</td>
                                                <td class="text-right tabular">{{ $file->sizeDisplay() }}</td>
                                                <td>
                                                    <div class="flex items-center justify-end gap-1">
                                                        <a href="{{ route('site.files.show', $file) }}" target="_blank" rel="noopener"
                                                           aria-label="Preview {{ $file->title }}" data-tip="Preview On The Public Site"
                                                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50">
                                                            <x-icon name="eye" class="w-4 h-4" />
                                                        </a>
                                                        <a href="{{ route('files.edit', $file) }}"
                                                           aria-label="Edit {{ $file->title }}" data-tip="Edit Details"
                                                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50">
                                                            <x-icon name="edit" class="w-4 h-4" />
                                                        </a>
                                                        <x-delete-button :name="'del-file-' . $file->id" :action="route('files.destroy', $file)"
                                                                         title="Delete This File?"
                                                                         message="The file is removed from disk and any page or notice linking to it will show a not-found page. This cannot be undone." />
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </x-table>
                            @else
                                <x-admin.empty title="No Files Here Yet" icon="folder"
                                               message="Upload ordinances, budgets, forms, and images from the Upload Files tab. Files you upload land in the folder you are currently browsing." />
                            @endif

                            {{-- Modals live inside the selection scope so they can read `selected`. --}}
                            <x-modal name="bulk-delete-files" title="Delete Selected Files?" tone="danger" icon="warning" maxWidth="max-w-md">
                                This permanently removes <span class="font-semibold" x-text="selected.length"></span> file(s) from
                                disk. Pages, notices, and meetings linking to them will show a not-found page. This cannot be undone.
                                <x-slot:footer>
                                    <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'bulk-delete-files')">Cancel</x-button>
                                    <x-button variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Delete Permanently</x-button>
                                </x-slot:footer>
                            </x-modal>

                            <x-modal name="bulk-move-files" title="Move Selected Files" icon="folder" maxWidth="max-w-md">
                                <p>Move <span class="font-semibold" x-text="selected.length"></span> selected file(s) into another folder.
                                   Public URLs do not change, so existing links keep working.</p>
                                <div class="mt-4">
                                    <label for="move-target" class="block text-sm font-medium text-slate-700">Destination Folder</label>
                                    <select id="move-target" x-model="moveTarget"
                                            class="mt-1.5 block w-full rounded-lg border-0 py-2 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                                        <option value="">Unfiled (No Folder)</option>
                                        @foreach ($folderTree as $node)
                                            <option value="{{ $node['folder']->id }}">{{ $node['prefix'] }}{{ $node['folder']->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-slot:footer>
                                    <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'bulk-move-files')">Cancel</x-button>
                                    <x-button size="sm" icon="folder" x-on:click="submitMove()">Move Files</x-button>
                                </x-slot:footer>
                            </x-modal>
                        </div>

                        @if ($files->hasPages())
                            <div class="border-t border-slate-100 px-4 py-3">{{ $files->links() }}</div>
                        @endif
                    </x-card>
                </x-tab-panel>

                {{-- ---------------------------------------------------------
                     Upload
                --------------------------------------------------------- --}}
                <x-tab-panel name="upload">
                    <x-card title="Upload Files"
                            subtitle="Drag files anywhere onto the box below, or browse for them. Up to 40 files at a time, {{ $maxUploadMb }} MB each.">
                        <form method="POST" action="{{ route('files.store') }}" enctype="multipart/form-data" class="space-y-5" data-upload-form>
                            @csrf
                            <input type="hidden" name="folder_id" value="{{ $folder?->id }}">

                            {{-- Drag-and-drop zone. Behaviour is in public/js/municipal.js;
                                 the plain file input underneath keeps it working without JS. --}}
                            <div data-dropzone
                                 class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/60 p-8 text-center transition">
                                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-white text-brand-600 ring-1 ring-brand-100">
                                    <x-icon name="upload" class="w-6 h-6" />
                                </span>
                                <p class="mt-3 text-sm font-medium text-slate-900">Drag And Drop Files Here</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Uploading Into
                                    <span class="font-medium text-slate-700">{{ $folder?->name ?? 'Unfiled (No Folder)' }}</span>
                                </p>

                                <div class="mt-5">
                                    <label for="files" class="block text-sm font-medium text-slate-700">Choose Files</label>
                                    <input id="files" name="files[]" type="file" multiple required data-dropzone-input
                                           class="mx-auto mt-2 block w-full max-w-md text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                                    @error('files')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                    @error('files.0')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                </div>

                                <ul data-dropzone-list class="mx-auto mt-4 max-w-md space-y-1 text-left text-sm text-slate-600"></ul>
                            </div>

                            <div class="section-divider"></div>

                            <x-field label="Visibility"
                                     hint="Staff Only files are never listed on, or downloadable from, the public site. You can change this per file afterwards.">
                                <x-toggle name="public_visibility" :checked="true"
                                          label="Publish These Files To The Public File Browser"
                                          description="Turn Off To Keep Them Staff Only." />
                            </x-field>

                            <div class="flex justify-end">
                                <x-button type="submit" icon="upload">Upload Files</x-button>
                            </div>
                        </form>
                    </x-card>
                </x-tab-panel>

                {{-- ---------------------------------------------------------
                     Folders
                --------------------------------------------------------- --}}
                <x-tab-panel name="folder">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <x-card title="New Folder" subtitle="Folders nest up to five levels deep.">
                            <form method="POST" action="{{ route('folders.store') }}" class="space-y-5">
                                @csrf
                                <x-field label="Folder Name" for="new-folder-name" required :error="$errors->first('name')">
                                    <x-input id="new-folder-name" name="name" required maxlength="120" value="{{ old('name') }}" />
                                </x-field>

                                <x-field label="Parent Folder" for="new-folder-parent" :error="$errors->first('parent_id')">
                                    <x-select id="new-folder-parent" name="parent_id">
                                        <option value="">No Parent (Top Level)</option>
                                        @foreach ($folderTree as $node)
                                            <option value="{{ $node['folder']->id }}" @selected(old('parent_id') == $node['folder']->id)>{{ $node['prefix'] }}{{ $node['folder']->name }}</option>
                                        @endforeach
                                    </x-select>
                                </x-field>

                                <x-field label="Description" for="new-folder-description" hint="Shown to residents under the folder name.">
                                    <x-input id="new-folder-description" name="description" maxlength="255" value="{{ old('description') }}" />
                                </x-field>

                                <x-field label="Folder Icon" for="new-folder-icon" hint="Shown In The Tree And On The Public Folder Card.">
                                    <x-select id="new-folder-icon" name="icon">
                                        @foreach ($folderIcons as $icon)
                                            <option value="{{ $icon }}" @selected(old('icon') === $icon)>{{ ucfirst($icon) }}</option>
                                        @endforeach
                                    </x-select>
                                </x-field>

                                <x-field label="Sort Order" for="new-folder-sort" hint="Lower Numbers Appear First.">
                                    <x-input id="new-folder-sort" name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}" />
                                </x-field>

                                <x-toggle name="is_public" :checked="true" label="Visible To The Public"
                                          description="Turn Off For An Internal, Staff Only Folder." />

                                <div class="flex justify-end">
                                    <x-button type="submit" icon="plus">Create Folder</x-button>
                                </div>
                            </form>
                        </x-card>

                        @if ($folder)
                            <x-card title="Edit Folder" :subtitle="$folder->name">
                                <form method="POST" action="{{ route('folders.update', $folder) }}" class="space-y-5">
                                    @csrf @method('PUT')
                                    <x-field label="Folder Name" for="folder-name" required :error="$errors->first('name')">
                                        <x-input id="folder-name" name="name" required maxlength="120" value="{{ $folder->name }}" />
                                    </x-field>

                                    <x-field label="Parent Folder" for="folder-parent" :error="$errors->first('parent_id')">
                                        <x-select id="folder-parent" name="parent_id">
                                            <option value="">No Parent (Top Level)</option>
                                            @foreach ($folderTree as $node)
                                                @if ($node['folder']->id !== $folder->id)
                                                    <option value="{{ $node['folder']->id }}" @selected($folder->parent_id === $node['folder']->id)>{{ $node['prefix'] }}{{ $node['folder']->name }}</option>
                                                @endif
                                            @endforeach
                                        </x-select>
                                    </x-field>

                                    <x-field label="Description" for="folder-description">
                                        <x-input id="folder-description" name="description" maxlength="255" value="{{ $folder->description }}" />
                                    </x-field>

                                    <x-field label="Folder Icon" for="folder-icon" hint="Shown In The Tree And On The Public Folder Card.">
                                        <x-select id="folder-icon" name="icon">
                                            @foreach ($folderIcons as $icon)
                                                <option value="{{ $icon }}" @selected($folder->icon === $icon)>{{ ucfirst($icon) }}</option>
                                            @endforeach
                                        </x-select>
                                    </x-field>

                                    <x-field label="Sort Order" for="folder-sort" hint="Lower Numbers Appear First.">
                                        <x-input id="folder-sort" name="sort_order" type="number" min="0" value="{{ $folder->sort_order }}" />
                                    </x-field>

                                    <x-toggle name="is_public" :checked="$folder->is_public" label="Visible To The Public"
                                              description="Turn Off For An Internal, Staff Only Folder." />

                                    <div class="flex items-center justify-between gap-2">
                                        <x-delete-button :name="'del-folder-' . $folder->id" :action="route('folders.destroy', $folder)"
                                                         label="Delete Folder"
                                                         title="Delete This Folder?"
                                                         confirm="Delete Folder"
                                                         message="Files in this folder are NOT deleted. They move up to the parent folder, and any subfolders move with them. Public URLs are unaffected." />
                                        <x-button type="submit" icon="check">Save Folder</x-button>
                                    </div>
                                </form>
                            </x-card>
                        @else
                            <x-card title="Edit Folder">
                                <x-admin.empty title="No Folder Selected" icon="folder"
                                               message="Open a folder from the tree on the left to rename it, move it, or change who can see it." />
                            </x-card>
                        @endif
                    </div>
                </x-tab-panel>
            </x-tabs>
        </div>
    </div>
</x-layouts.app>
