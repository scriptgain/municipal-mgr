<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('site.departments.index', [
            'departments' => Department::published()->ordered()->withCount('staff')->get(),
        ]);
    }

    public function show(Department $department)
    {
        abort_unless($department->is_published || auth()->user()?->canEditContent(), 404);

        $department->load(['staff' => fn ($q) => $q->where('is_published', true), 'head']);

        return view('site.departments.show', [
            'department' => $department,
            'pages' => $department->pages()->published()->orderBy('sort_order')->get(),
            'documents' => $department->documents()->publiclyVisible()->latest('document_date')->limit(10)->get(),
            'news' => $department->news()->published()->latestFirst()->limit(4)->get(),
            'events' => $department->events()->published()->upcoming()->limit(4)->get(),
            'jobs' => $department->jobPostings()->open()->limit(5)->get(),
        ]);
    }
}
