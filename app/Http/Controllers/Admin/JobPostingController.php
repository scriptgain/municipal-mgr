<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Document;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class JobPostingController extends AdminController
{
    protected string $model = JobPosting::class;
    protected string $views = 'jobs';
    protected string $routes = 'jobs';
    protected string $label = 'Job Posting';
    protected array $with = ['department'];
    protected array $searchable = ['title', 'description'];
    protected array $orderBy = ['created_at', 'desc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'employment_type' => ['required', 'string', 'max:60'],
            'salary_range' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'apply_url' => ['nullable', 'url', 'max:255'],
            'apply_email' => ['nullable', 'email', 'max:150'],
            'application_document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'posted_on' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,published,closed'],
        ];
    }

    protected function formData(): array
    {
        return [
            'departments' => Department::ordered()->get(['id', 'name']),
            'types' => ['Full Time', 'Part Time', 'Seasonal', 'Temporary', 'Volunteer', 'Contract'],
            'documents' => Document::orderBy('title')->limit(300)->get(['id', 'title']),
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['is_open_until_filled'] = $request->boolean('is_open_until_filled');
        $data['posted_on'] = $data['posted_on'] ?? now()->toDateString();

        return $data;
    }
}
