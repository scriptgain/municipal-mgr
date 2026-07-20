<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends AdminController
{
    protected string $model = Document::class;
    protected string $views = 'documents';
    protected string $routes = 'documents';
    protected string $label = 'Document';
    protected array $with = ['category', 'department'];
    protected array $searchable = ['title', 'description', 'keywords', 'reference'];
    protected array $orderBy = ['created_at', 'desc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'document_category_id' => ['nullable', 'integer', 'exists:document_categories,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:120'],
            'document_date' => ['nullable', 'date'],
            // Required on create, optional on edit (replacing the file is opt-in).
            'file' => [$record ? 'nullable' : 'required', 'file', 'max:51200',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,rtf,jpg,jpeg,png,zip'],
        ];
    }

    protected function formData(): array
    {
        return [
            'categories' => DocumentCategory::orderBy('sort_order')->get(['id', 'name']),
            'departments' => Department::ordered()->get(['id', 'name']),
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        unset($data['file']);
        $data['is_published'] = $request->boolean('is_published', true);
        $data['uploaded_by'] = $record?->uploaded_by ?: auth()->id();

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        $old = $record->file_path;
        $file = $request->file('file');
        $path = $this->storeUpload($request, 'file', 'documents');

        $record->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        // Replacing a file removes the superseded copy so the library does not
        // silently grow a shadow archive of every revision ever uploaded.
        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }
    }
}
