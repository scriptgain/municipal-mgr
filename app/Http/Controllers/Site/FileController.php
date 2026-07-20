<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FileItem;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The public file browser.
 *
 * Residents browse published files by folder and search across the whole
 * library. This replaces the old /documents screens; those URLs still resolve
 * (see the legacy* methods below and routes/web.php) because municipal
 * documents get deep-linked from agendas, newsletters, and emails that nobody
 * can go back and edit.
 */
class FileController extends Controller
{
    public function index(Request $request)
    {
        $allFolders = Folder::visible()->ordered()->get();

        // ?category= is the old Document Library parameter. Honoured so that
        // bookmarked and emailed filter links keep working.
        $folderSlug = $request->query('folder') ?: $request->query('category');
        $folder = $folderSlug ? $allFolders->firstWhere('slug', $folderSlug) : null;

        // A slug that names no folder residents may see is a 404, not a silent
        // fall-back to the full listing: staff-only folders must not read as
        // "empty", and a typo should not look like a valid page.
        abort_if($folderSlug && ! $folder, 404);

        $query = FileItem::publiclyVisible()
            ->with(['folder', 'department'])
            ->search($request->query('q'));

        // Only files in folders residents may see, or unfiled files.
        $visibleFolderIds = $allFolders->pluck('id');
        $query->where(fn ($q) => $q->whereIn('folder_id', $visibleFolderIds)->orWhereNull('folder_id'));

        if ($folder) {
            $query->whereIn('folder_id', $folder->descendantIds($allFolders));
        }
        if ($department = $request->query('department')) {
            $query->whereHas('department', fn ($q) => $q->where('slug', $department));
        }
        if ($year = $request->query('year')) {
            $query->whereYear('document_date', $year);
        }
        if ($kind = $request->query('kind')) {
            $query->kind($kind);
        }

        $files = $query->orderByRaw('COALESCE(document_date, created_at) DESC')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('site.files.index', [
            'files' => $files,
            'folder' => $folder,
            'crumbs' => $this->crumbs($folder),
            'childFolders' => $folder
                ? $allFolders->where('parent_id', $folder->id)
                : $allFolders->whereNull('parent_id'),
            'folderCounts' => $this->folderCounts($allFolders),
            'departments' => Department::published()->ordered()->get(['name', 'slug']),
            'search' => $request->query('q'),
            'activeFolder' => $folder?->slug,
            'activeDepartment' => $department,
            'activeKind' => $kind,
        ]);
    }

    public function show(FileItem $file)
    {
        abort_unless($this->mayView($file), 404);

        $file->load('folder', 'department');
        seo()->for($file);

        return view('site.files.show', [
            'file' => $file,
            'crumbs' => array_merge($this->crumbs($file->folder, false), [['label' => $file->title, 'href' => null]]),
            'related' => FileItem::publiclyVisible()
                ->where('folder_id', $file->folder_id)
                ->whereNotNull('folder_id')
                ->where('id', '!=', $file->id)
                ->orderByRaw('COALESCE(document_date, created_at) DESC')
                ->limit(6)->get(),
        ]);
    }

    /**
     * Streamed download with a counted hit. Served through PHP rather than a
     * direct file link so unpublishing a file actually removes access, and so
     * the download counter stays accurate.
     */
    public function download(FileItem $file)
    {
        abort_unless($this->mayView($file), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($file->path), 404);

        $file->increment('download_count');

        return $disk->download($file->path, $file->file_name);
    }

    /* ------------------------------------------------------------------ */
    /* Legacy /documents URLs. Permanent redirects, so search engines and    */
    /* link checkers update while printed and emailed links keep working.    */
    /* ------------------------------------------------------------------ */

    public function legacyIndex(Request $request)
    {
        return redirect()->route('site.files', $request->query(), 301);
    }

    public function legacyShow(string $slug)
    {
        $file = FileItem::where('slug', $slug)->firstOrFail();

        return redirect()->route('site.files.show', $file->slug, 301);
    }

    public function legacyDownload(string $slug)
    {
        $file = FileItem::where('slug', $slug)->firstOrFail();

        return redirect()->route('site.files.download', $file->slug, 301);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Breadcrumb trail for the hero. Built here, not in the template: a Blade
     * view stays markup only (no array_merge, no collection mapping).
     */
    private function crumbs(?Folder $folder, bool $folderIsCurrentPage = true): array
    {
        $crumbs = [['label' => 'Files', 'href' => route('site.files')]];

        foreach ($folder ? $folder->trail() : [] as $node) {
            $crumbs[] = ['label' => $node->name, 'href' => route('site.files', ['folder' => $node->slug])];
        }

        // The crumb for the page you are already on is not a link. On a file's
        // own page the folder crumb stays clickable and the file is appended.
        if ($folderIsCurrentPage) {
            $crumbs[array_key_last($crumbs)]['href'] = null;
        }

        return $crumbs;
    }

    /** Staff may see unpublished and staff-only files; residents may not. */
    private function mayView(FileItem $file): bool
    {
        if ($file->is_published && $file->isPublic() && ($file->folder?->is_public ?? true)) {
            return true;
        }

        return (bool) auth()->user()?->canEditContent();
    }

    /** Published file count per folder, including everything nested beneath it. */
    private function folderCounts($allFolders): array
    {
        $direct = FileItem::publiclyVisible()
            ->whereNotNull('folder_id')
            ->selectRaw('folder_id, COUNT(*) as total')
            ->groupBy('folder_id')
            ->pluck('total', 'folder_id');

        $counts = [];
        foreach ($allFolders as $folder) {
            $counts[$folder->id] = collect($folder->descendantIds($allFolders))
                ->sum(fn ($id) => (int) ($direct[$id] ?? 0));
        }

        return $counts;
    }
}
