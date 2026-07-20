<?php

namespace App\Http\Controllers\Admin;

use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DocumentCategoryController extends AdminController
{
    protected string $model = DocumentCategory::class;
    protected string $views = 'document-categories';
    protected string $routes = 'document-categories';
    protected string $label = 'Document Category';
    protected array $searchable = ['name', 'description'];
    protected array $orderBy = ['sort_order', 'asc'];
    protected bool $departmentScoped = false;

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function formData(): array
    {
        return ['icons' => ['folder', 'book', 'archive', 'shield', 'building', 'globe', 'edit', 'database']];
    }
}
