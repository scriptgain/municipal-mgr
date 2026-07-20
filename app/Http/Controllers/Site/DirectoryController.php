<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StaffMember;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        $query = StaffMember::published()->with('department')->orderBy('name');

        if ($term = trim((string) $request->query('q'))) {
            $like = '%' . $term . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)
                ->orWhere('job_title', 'like', $like)
                ->orWhere('office', 'like', $like));
        }
        if ($department = $request->query('department')) {
            $query->whereHas('department', fn ($q) => $q->where('slug', $department));
        }

        return view('site.directory', [
            'staff' => $query->get(),
            'departments' => Department::published()->ordered()->get(['name', 'slug']),
            'search' => $term ?? null,
            'activeDepartment' => $department,
        ]);
    }
}
