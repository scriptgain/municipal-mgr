<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\StaffMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class StaffController extends AdminController
{
    protected string $model = StaffMember::class;
    protected string $views = 'staff';
    protected string $routes = 'staff';
    protected string $label = 'Staff Member';
    protected array $with = ['department'];
    protected array $searchable = ['name', 'job_title', 'email'];
    protected array $orderBy = ['name', 'asc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'job_title' => ['required', 'string', 'max:150'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'extension' => ['nullable', 'string', 'max:16'],
            'office' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function formData(): array
    {
        return ['departments' => Department::ordered()->get(['id', 'name'])];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        unset($data['photo']);
        $data['is_published'] = $request->boolean('is_published', true);

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        if ($path = $this->storeUpload($request, 'photo', 'staff')) {
            $record->update(['photo_path' => $path]);
        }
    }
}
