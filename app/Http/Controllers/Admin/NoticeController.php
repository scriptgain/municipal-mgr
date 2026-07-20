<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Document;
use App\Models\Notice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NoticeController extends AdminController
{
    protected string $model = Notice::class;
    protected string $views = 'notices';
    protected string $routes = 'notices';
    protected string $label = 'Public Notice';
    protected array $with = ['department', 'document'];
    protected array $searchable = ['title', 'body'];
    protected array $orderBy = ['posted_at', 'desc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'notice_type' => ['required', 'string', 'max:60'],
            'body' => ['nullable', 'string'],
            'posted_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:posted_at'],
            'status' => ['required', 'in:draft,published'],
        ];
    }

    protected function formData(): array
    {
        return [
            'departments' => Department::ordered()->get(['id', 'name']),
            'documents' => Document::orderBy('title')->limit(300)->get(['id', 'title']),
            'noticeTypes' => ['Public Hearing', 'Ordinance', 'Resolution', 'Election', 'Bid Notice', 'General'],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['posted_at'] = $data['posted_at'] ?? now();

        return $data;
    }
}
