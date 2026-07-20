<?php

namespace App\Http\Controllers\Admin;

use App\Models\Official;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class OfficialController extends AdminController
{
    protected string $model = Official::class;
    protected string $views = 'officials';
    protected string $routes = 'officials';
    protected string $label = 'Elected Official';
    protected array $searchable = ['name', 'office', 'district'];
    protected array $orderBy = ['sort_order', 'asc'];
    protected bool $departmentScoped = false;

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'office' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'bio' => ['nullable', 'string'],
            'term_start' => ['nullable', 'date'],
            'term_end' => ['nullable', 'date', 'after:term_start'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function formData(): array
    {
        return [
            'offices' => ['Mayor', 'Vice Mayor', 'Council Member', 'Town Clerk', 'Town Treasurer', 'Town Manager', 'Town Attorney'],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        unset($data['photo']);
        $data['is_current'] = $request->boolean('is_current', true);
        $data['is_published'] = $request->boolean('is_published', true);

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        if ($path = $this->storeUpload($request, 'photo', 'officials')) {
            $record->update(['photo_path' => $path]);
        }
    }

    protected function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->isEditor();
    }
}
