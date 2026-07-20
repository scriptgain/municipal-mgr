<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\NewsPost;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsPost::published()->with('department')->latestFirst();

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($department = $request->query('department')) {
            $query->whereHas('department', fn ($q) => $q->where('slug', $department));
        }

        return view('site.news.index', [
            'posts' => $query->paginate(12)->withQueryString(),
            'categories' => NewsPost::published()->distinct()->orderBy('category')->pluck('category'),
            'departments' => Department::published()->ordered()->get(['name', 'slug']),
            'activeCategory' => $category,
            'activeDepartment' => $department,
        ]);
    }

    public function show(NewsPost $newsPost)
    {
        abort_unless($newsPost->isPublished() || auth()->user()?->canEditContent(), 404);

        return view('site.news.show', [
            'post' => $newsPost->load('department', 'author'),
            'related' => NewsPost::published()
                ->where('id', '!=', $newsPost->id)
                ->when($newsPost->department_id, fn ($q) => $q->where('department_id', $newsPost->department_id))
                ->latestFirst()->limit(3)->get(),
        ]);
    }
}
