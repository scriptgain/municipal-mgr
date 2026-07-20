<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\NewsPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NewsController extends AdminController
{
    protected string $model = NewsPost::class;
    protected string $views = 'news';
    protected string $routes = 'news';
    protected string $label = 'News Post';
    protected array $with = ['department', 'author'];
    protected array $searchable = ['title', 'excerpt', 'body'];
    protected array $orderBy = ['published_at', 'desc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'category' => ['required', 'string', 'max:60'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:8192'],
        ];
    }

    protected function formData(): array
    {
        return [
            'departments' => Department::ordered()->get(['id', 'name']),
            'categories' => ['News', 'Announcement', 'Press Release', 'Service Alert'],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        unset($data['image']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['author_id'] = $record?->author_id ?: auth()->id();
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        if ($path = $this->storeUpload($request, 'image', 'news')) {
            $record->update(['image_path' => $path]);
        }
    }
}
