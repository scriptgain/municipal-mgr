<?php

namespace App\Http\Controllers\Admin;

use App\Models\FileItem;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MeetingController extends AdminController
{
    protected string $model = Meeting::class;
    protected string $views = 'meetings';
    protected string $routes = 'meetings';
    protected string $label = 'Meeting';
    protected array $with = ['agenda', 'minutes'];
    protected array $searchable = ['body', 'title', 'summary'];
    protected array $orderBy = ['meets_at', 'desc'];
    protected bool $departmentScoped = false;

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'body' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:150'],
            'meets_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'agenda_document_id' => ['nullable', 'integer', 'exists:files,id'],
            'minutes_document_id' => ['nullable', 'integer', 'exists:files,id'],
            'packet_document_id' => ['nullable', 'integer', 'exists:files,id'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'in:scheduled,cancelled,held'],
        ];
    }

    protected function formData(): array
    {
        return [
            'bodies' => config('municipal.meeting_bodies'),
            'documents' => FileItem::whereIn('kind', [FileItem::KIND_DOCUMENT, FileItem::KIND_OTHER])->orderByDesc('document_date')->limit(300)->get(['id', 'title']),
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['is_published'] = $request->boolean('is_published', true);

        return $data;
    }
}
