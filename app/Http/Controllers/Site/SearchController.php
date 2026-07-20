<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\NewsPost;
use App\Models\Notice;
use App\Models\Page;
use Illuminate\Http\Request;

/**
 * Site-wide search. Grouped by content type rather than one blended list —
 * residents searching a municipal site are usually looking for a specific KIND
 * of thing ("the budget PDF", "the council agenda"), not a relevance ranking.
 */
class SearchController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q'));
        $results = ['pages' => collect(), 'documents' => collect(), 'news' => collect(),
            'notices' => collect(), 'meetings' => collect(), 'departments' => collect()];
        $total = 0;

        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

            $results['pages'] = Page::published()
                ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('summary', 'like', $like))
                ->limit(10)->get();

            $results['documents'] = Document::published()->search($term)
                ->orderByDesc('document_date')->limit(10)->get();

            $results['news'] = NewsPost::published()
                ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like))
                ->latestFirst()->limit(10)->get();

            $results['notices'] = Notice::current()
                ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('body', 'like', $like))
                ->limit(10)->get();

            $results['meetings'] = Meeting::published()
                ->where(fn ($q) => $q->where('body', 'like', $like)->orWhere('title', 'like', $like))
                ->orderByDesc('meets_at')->limit(10)->get();

            $results['departments'] = Department::published()
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('summary', 'like', $like))
                ->limit(10)->get();

            $total = collect($results)->sum(fn ($c) => $c->count());
        }

        return view('site.search', [
            'term' => $term,
            'results' => $results,
            'total' => $total,
        ]);
    }
}
