<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\StaffMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DepartmentController extends AdminController
{
    protected string $model = Department::class;
    protected string $views = 'departments';
    protected string $routes = 'departments';
    protected string $label = 'Department';
    protected array $with = ['head'];
    protected array $searchable = ['name', 'summary'];
    protected array $orderBy = ['sort_order', 'asc'];
    // Departments themselves are structural: only site editors and admins.
    protected bool $departmentScoped = false;

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:40'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:40'],
            'fax' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:255'],
            'head_staff_id' => ['nullable', 'integer', 'exists:staff_members,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function formData(): array
    {
        return [
            'staff' => StaffMember::orderBy('name')->get(['id', 'name', 'job_title']),
            'icons' => ['building', 'shield', 'book', 'users', 'globe', 'bolt', 'home', 'folder', 'settings', 'clock'],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['is_published'] = $request->boolean('is_published', true);

        return $data;
    }

    protected function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->isEditor();
    }
}
