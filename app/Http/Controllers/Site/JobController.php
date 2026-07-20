<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;

class JobController extends Controller
{
    public function index()
    {
        return view('site.jobs.index', [
            'jobs' => JobPosting::open()->with('department')->orderBy('closes_at')->get(),
            'closed' => JobPosting::where('status', 'published')
                ->whereNotNull('closes_at')->where('closes_at', '<', now())
                ->orderByDesc('closes_at')->limit(10)->get(),
        ]);
    }

    public function show(JobPosting $jobPosting)
    {
        abort_unless($jobPosting->status === 'published' || auth()->user()?->canEditContent(), 404);

        return view('site.jobs.show', [
            'job' => $jobPosting->load('department', 'applicationForm'),
        ]);
    }
}
