<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bid;
use App\Models\Department;
use App\Models\FileItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BidController extends AdminController
{
    protected string $model = Bid::class;
    protected string $views = 'bids';
    protected string $routes = 'bids';
    protected string $label = 'Bid Or RFP';
    protected array $with = ['department', 'document'];
    protected array $searchable = ['title', 'reference', 'description'];
    protected array $orderBy = ['closes_at', 'desc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'reference' => ['nullable', 'string', 'max:120'],
            'bid_type' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'document_id' => ['nullable', 'integer', 'exists:files,id'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'pre_bid_meeting_at' => ['nullable', 'date'],
            'status' => ['required', 'in:open,closed,awarded,cancelled'],
            'awarded_to' => ['nullable', 'string', 'max:200'],
        ];
    }

    protected function formData(): array
    {
        return [
            'departments' => Department::ordered()->get(['id', 'name']),
            'types' => ['Bid', 'RFP', 'RFQ', 'Sole Source', 'Cooperative Purchase'],
            'documents' => FileItem::whereIn('kind', [FileItem::KIND_DOCUMENT, FileItem::KIND_OTHER])->orderBy('title')->limit(300)->get(['id', 'title']),
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['is_published'] = $request->boolean('is_published', true);

        return $data;
    }
}
