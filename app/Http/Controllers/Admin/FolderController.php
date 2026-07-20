<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FileItem;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Folder CRUD for the unified File Manager.
 *
 * Folders are created and edited inline from the file browser (modal forms),
 * so there are no separate create/edit screens — just the write verbs.
 */
class FolderController extends Controller
{
    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $this->validated($request);
        $folder = Folder::create($data);

        return redirect()
            ->route('files.index', ['folder' => $folder->slug])
            ->with('status', "Folder \"{$folder->name}\" Created.");
    }

    public function update(Request $request, Folder $folder)
    {
        $this->authorizeAccess();

        $data = $this->validated($request, $folder);
        $folder->update($data);

        return redirect()
            ->route('files.index', ['folder' => $folder->slug])
            ->with('status', "Folder \"{$folder->name}\" Saved.");
    }

    /**
     * Deleting a folder never deletes the files inside it. Child folders and
     * their files are lifted to the deleted folder's parent, so a mis-click
     * cannot silently destroy a municipality's ordinance archive.
     */
    public function destroy(Request $request, Folder $folder)
    {
        $this->authorizeAccess();

        $name = $folder->name;
        $parentId = $folder->parent_id;

        // Optional hard delete of contents, only when explicitly requested
        // from the modal's toggle.
        if ($request->boolean('delete_files')) {
            foreach ($folder->files as $file) {
                Storage::disk('public')->delete($file->path);
                $file->delete();
            }
        } else {
            FileItem::where('folder_id', $folder->id)->update(['folder_id' => $parentId]);
        }

        Folder::where('parent_id', $folder->id)->update(['parent_id' => $parentId]);
        $folder->delete();

        return redirect()
            ->route('files.index', $parentId ? ['folder' => Folder::find($parentId)?->slug] : [])
            ->with('status', "Folder \"{$name}\" Deleted.");
    }

    /* ------------------------------------------------------------------ */

    private function validated(Request $request, ?Folder $folder = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        /*
         | Every key below is normalised with ?? because a `nullable` rule only
         | puts a key in the validated array when it was actually submitted.
         | An empty form field is absent, not null, so reading $data['icon']
         | directly throws "Undefined array key".
         */
        $data['is_public'] = $request->boolean('is_public');
        $data['icon'] = ($data['icon'] ?? null) ?: 'folder';
        $data['description'] = $data['description'] ?? null;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['parent_id'] = ($data['parent_id'] ?? null) ?: null;
        $parentId = $data['parent_id'];

        if ($folder && $parentId) {
            // A folder cannot be moved inside itself or inside its own
            // descendant: that detaches the whole subtree from the tree.
            if (in_array((int) $parentId, $folder->descendantIds(), true)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A Folder Cannot Be Moved Inside Itself Or One Of Its Own Subfolders.',
                ]);
            }
        }

        if ($parentId) {
            $parent = Folder::find($parentId);
            if ($parent && $parent->depth() + 1 > Folder::MAX_DEPTH) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Folders May Be Nested Up To ' . Folder::MAX_DEPTH . ' Levels Deep.',
                ]);
            }
        }

        return $data;
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->canEditContent(), 403, 'You May Not Manage Folders.');
    }
}
