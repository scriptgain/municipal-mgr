<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EventController extends AdminController
{
    protected string $model = Event::class;
    protected string $views = 'events';
    protected string $routes = 'events';
    protected string $label = 'Event';
    protected array $with = ['department'];
    protected array $searchable = ['title', 'description', 'location'];
    protected array $orderBy = ['starts_at', 'desc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'category' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:255'],
            'registration_url' => ['nullable', 'url', 'max:255'],
            'image' => ['nullable', 'image', 'max:8192'],
        ];
    }

    protected function formData(): array
    {
        return [
            'departments' => Department::ordered()->get(['id', 'name']),
            'categories' => ['Community', 'Meeting', 'Holiday', 'Closure', 'Recreation', 'Election'],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        unset($data['image']);
        $data['all_day'] = $request->boolean('all_day');
        $data['is_published'] = $request->boolean('is_published', true);

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        if ($path = $this->storeUpload($request, 'image', 'events')) {
            $record->update(['image_path' => $path]);
        }
    }
}
