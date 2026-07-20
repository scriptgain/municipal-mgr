<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\FileItem;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The unified File Manager (staff side).
 *
 * Replaces the old Document Library + Document Categories + Media Library
 * screens with one browser: a folder tree on the left, the current folder's
 * files on the right, bulk upload, bulk move, and massSelect bulk delete.
 *
 * Not built on AdminController: that base class assumes a flat paginated table
 * of one resource, and this screen is a folder-scoped browser with two
 * different result shapes (grid for images, table for documents).
 */
class FileController extends Controller
{
    /** Upload cap per file, in kilobytes (50 MB). Municipal budget PDFs are large. */
    private const MAX_UPLOAD_KB = 51200;

    private const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,rtf,odt,ods,jpg,jpeg,png,gif,webp,svg,zip';

    /** Icons a folder may use. Must exist in resources/views/components/icon.blade.php. */
    public const FOLDER_ICONS = ['folder', 'book', 'archive', 'shield', 'building', 'globe', 'edit', 'database', 'scale', 'clipboard', 'map-pin', 'clock'];

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $allFolders = Folder::ordered()->get();
        $folder = null;
        if ($slug = $request->query('folder')) {
            $folder = $allFolders->firstWhere('slug', $slug);
        }

        $search = trim((string) $request->query('q'));
        $kind = $request->query('kind') ?: null;
        $visibility = $request->query('visibility') ?: null;

        $query = FileItem::with(['folder', 'department', 'uploader']);

        /*
         | A search spans the whole library; browsing is scoped to the open
         | folder. Searching only inside the current folder is the behaviour
         | people complain about most in file managers.
         */
        if ($search !== '') {
            $query->search($search);
            if ($folder) {
                $query->whereIn('folder_id', $folder->descendantIds($allFolders));
            }
        } else {
            $query->inFolder($folder?->id);
        }

        $query->kind($kind)->visibility($visibility);
        $this->scopeToDepartment($query);

        $files = $query->orderByRaw('COALESCE(document_date, created_at) DESC')
            ->orderByDesc('id')
            ->paginate((int) config('municipal.rows_per_page', 25))
            ->withQueryString();

        return view('admin.files.index', [
            'files' => $files,
            'folder' => $folder,
            'folderTree' => Folder::tree($allFolders),
            'allFolders' => $allFolders,
            'childFolders' => $folder ? $folder->children : $allFolders->whereNull('parent_id')->sortBy('sort_order'),
            'departments' => Department::ordered()->get(['id', 'name']),
            'search' => $search,
            'activeKind' => $kind,
            'activeVisibility' => $visibility,
            'counts' => $this->counts(),
            'folderIcons' => self::FOLDER_ICONS,
            'maxUploadMb' => (int) (self::MAX_UPLOAD_KB / 1024),
        ]);
    }

    /**
     * Bulk upload. Accepts many files in one request (the drag-and-drop zone
     * posts the whole drop at once) and files them into the open folder.
     */
    public function store(Request $request)
    {
        $this->authorizeAccess();

        $request->validate([
            'files' => ['required', 'array', 'max:40'],
            'files.*' => ['file', 'max:' . self::MAX_UPLOAD_KB, 'mimes:' . self::ALLOWED_MIMES],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $folderId = $request->input('folder_id') ?: null;

        // The upload form carries a toggle switch, not a select, so visibility
        // arrives as a boolean and is mapped to the stored enum here.
        $visibility = $request->boolean('public_visibility', true)
            ? FileItem::VISIBILITY_PUBLIC
            : FileItem::VISIBILITY_STAFF;

        $created = 0;
        foreach ($request->file('files') as $upload) {
            $original = $upload->getClientOriginalName();
            $base = pathinfo($original, PATHINFO_FILENAME) ?: 'file';
            $stored = Str::slug($base) . '-' . Str::lower(Str::random(6)) . '.' . $upload->getClientOriginalExtension();
            $path = Storage::disk('public')->putFileAs('files', $upload, $stored);

            $kind = FileItem::kindFor($upload->getClientMimeType(), $original);
            $dimensions = $kind === FileItem::KIND_IMAGE ? @getimagesize($upload->getRealPath()) : false;

            FileItem::create([
                'folder_id' => $folderId,
                'title' => Str::headline($base),
                'description' => null,
                'path' => $path,
                'file_name' => $original,
                'mime_type' => $upload->getClientMimeType(),
                'size' => $upload->getSize(),
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
                'is_published' => true,
                'visibility' => $visibility,
                'kind' => $kind,
                'uploaded_by' => auth()->id(),
            ]);
            $created++;
        }

        return back()->with('status', "{$created} File(s) Uploaded.");
    }

    public function edit(FileItem $file)
    {
        $this->authorizeFile($file);

        return view('admin.files.edit', [
            'file' => $file->load('folder', 'department'),
            'folderTree' => Folder::tree(),
            'departments' => Department::ordered()->get(['id', 'name']),
        ]);
    }

    /**
     * Metadata edit, including rename and an optional file replacement.
     * The slug is never regenerated: a published municipal URL may be printed
     * on a mailer or cited in an ordinance.
     */
    public function update(Request $request, FileItem $file)
    {
        $this->authorizeFile($file);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'replacement' => ['nullable', 'file', 'max:' . self::MAX_UPLOAD_KB, 'mimes:' . self::ALLOWED_MIMES],

            // Search Appearance panel. This controller is not an AdminController
            // subclass, so it declares the SEO rules itself rather than getting
            // them from rulesWithSeo().
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'og_image_file' => ['nullable', 'image', 'max:8192'],
        ]);

        // Both of these are toggle switches on the form, so they arrive as
        // booleans rather than as the values stored on the row.
        $data['is_published'] = $request->boolean('is_published');
        $data['noindex'] = $request->boolean('noindex');
        unset($data['og_image_file']);

        if ($seoImage = $request->file('og_image_file')) {
            $name = 'og-' . Str::lower(Str::random(8)) . '.' . $seoImage->getClientOriginalExtension();
            $data['og_image'] = Storage::disk('public')->putFileAs('seo', $seoImage, $name);
        }
        $data['visibility'] = $request->boolean('public_visibility')
            ? FileItem::VISIBILITY_PUBLIC
            : FileItem::VISIBILITY_STAFF;
        unset($data['replacement']);
        $file->fill($data);

        if ($upload = $request->file('replacement')) {
            $old = $file->path;
            $original = $upload->getClientOriginalName();
            $stored = Str::slug(pathinfo($original, PATHINFO_FILENAME) ?: 'file')
                . '-' . Str::lower(Str::random(6)) . '.' . $upload->getClientOriginalExtension();
            $path = Storage::disk('public')->putFileAs('files', $upload, $stored);

            $kind = FileItem::kindFor($upload->getClientMimeType(), $original);
            $dimensions = $kind === FileItem::KIND_IMAGE ? @getimagesize($upload->getRealPath()) : false;

            $file->fill([
                'path' => $path,
                'file_name' => $original,
                'mime_type' => $upload->getClientMimeType(),
                'size' => $upload->getSize(),
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
                'kind' => $kind,
            ]);

            // Replacing a file removes the superseded copy so the library does
            // not silently grow a shadow archive of every revision uploaded.
            if ($old && $old !== $path) {
                Storage::disk('public')->delete($old);
            }
        }

        $file->save();

        return redirect()
            ->route('files.index', $file->folder ? ['folder' => $file->folder->slug] : [])
            ->with('status', "File \"{$file->title}\" Saved.");
    }

    public function destroy(FileItem $file)
    {
        $this->authorizeFile($file);

        $title = $file->title;
        Storage::disk('public')->delete($file->path);
        $file->delete();

        return back()->with('status', "File \"{$title}\" Deleted.");
    }

    /** massSelect bulk delete, always behind a modal confirm. */
    public function bulkDestroy(Request $request)
    {
        $this->authorizeAccess();

        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if (! $ids) {
            return back()->with('warning', 'No Files Were Selected.');
        }

        $query = FileItem::whereIn('id', $ids);
        $this->scopeToDepartment($query);

        $n = 0;
        foreach ($query->get() as $file) {
            Storage::disk('public')->delete($file->path);
            $file->delete();
            $n++;
        }

        AuditLog::record('bulk-deleted', "{$n} file(s) deleted in bulk");

        return back()->with('status', "{$n} File(s) Deleted.");
    }

    /** Bulk move into another folder (or to Unfiled when no folder is chosen). */
    public function bulkMove(Request $request)
    {
        $this->authorizeAccess();

        $request->validate(['folder_id' => ['nullable', 'integer', 'exists:folders,id']]);

        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if (! $ids) {
            return back()->with('warning', 'No Files Were Selected.');
        }

        $query = FileItem::whereIn('id', $ids);
        $this->scopeToDepartment($query);
        $n = $query->update(['folder_id' => $request->input('folder_id') ?: null]);

        $target = $request->input('folder_id')
            ? Folder::find($request->input('folder_id'))?->name
            : 'Unfiled';

        AuditLog::record('updated', "{$n} file(s) moved to \"{$target}\"");

        return back()->with('status', "{$n} File(s) Moved To \"{$target}\".");
    }

    /* ------------------------------------------------------------------ */

    private function counts(): array
    {
        $base = fn () => tap(FileItem::query(), fn ($q) => $this->scopeToDepartment($q));

        return [
            'all' => $base()->count(),
            'images' => $base()->where('kind', FileItem::KIND_IMAGE)->count(),
            'documents' => $base()->where('kind', FileItem::KIND_DOCUMENT)->count(),
            'staff_only' => $base()->where('visibility', FileItem::VISIBILITY_STAFF)->count(),
        ];
    }

    /** Department editors only see and touch their own department's files. */
    private function scopeToDepartment($query): void
    {
        $user = auth()->user();
        if ($user && $user->isDepartmentEditor()) {
            $query->where(fn ($q) => $q
                ->where('department_id', $user->department_id)
                ->orWhereNull('department_id'));
        }
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->canEditContent(), 403, 'You May Not Manage Files.');
    }

    private function authorizeFile(FileItem $file): void
    {
        $this->authorizeAccess();

        $user = auth()->user();
        if ($user->isDepartmentEditor() && $file->department_id) {
            abort_unless(
                $user->canEditDepartment($file->department_id),
                403,
                'You May Only Edit Your Own Department\'s Files.'
            );
        }
    }
}
