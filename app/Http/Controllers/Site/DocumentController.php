<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::published()->with(['category', 'department'])
            ->search($request->query('q'));

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }
        if ($department = $request->query('department')) {
            $query->whereHas('department', fn ($q) => $q->where('slug', $department));
        }
        if ($year = $request->query('year')) {
            $query->whereYear('document_date', $year);
        }

        return view('site.documents.index', [
            'documents' => $query->orderByDesc('document_date')->orderByDesc('id')
                ->paginate(20)->withQueryString(),
            'categories' => DocumentCategory::orderBy('sort_order')->withCount(['documents' => fn ($q) => $q->where('is_published', true)])->get(),
            'departments' => Department::published()->ordered()->get(['name', 'slug']),
            'search' => $request->query('q'),
            'activeCategory' => $category,
            'activeDepartment' => $department,
        ]);
    }

    public function show(Document $document)
    {
        abort_unless($document->is_published, 404);

        return view('site.documents.show', [
            'document' => $document->load('category', 'department'),
            'related' => Document::published()
                ->where('document_category_id', $document->document_category_id)
                ->where('id', '!=', $document->id)
                ->orderByDesc('document_date')->limit(6)->get(),
        ]);
    }

    /**
     * Streamed download with a counted hit. Served through PHP rather than a
     * direct file link so unpublishing a document actually removes access.
     */
    public function download(Document $document)
    {
        abort_unless($document->is_published, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($document->file_path), 404);

        $document->increment('download_count');

        return $disk->download($document->file_path, $document->file_name);
    }
}
