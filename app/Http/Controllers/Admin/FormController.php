<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\FormDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormController extends AdminController
{
    protected string $model = FormDefinition::class;
    protected string $views = 'forms';
    protected string $routes = 'forms';
    protected string $label = 'Form';
    protected array $with = ['department'];
    protected array $searchable = ['name', 'description'];
    protected array $orderBy = ['name', 'asc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notify_email' => ['nullable', 'email', 'max:150'],
            'success_message' => ['nullable', 'string', 'max:1000'],
            'fields' => ['nullable', 'array'],
            'fields.*.label' => ['nullable', 'string', 'max:150'],
            'fields.*.type' => ['nullable', 'string', 'max:30'],
            'fields.*.options' => ['nullable', 'string', 'max:1000'],
            'fields.*.help' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function formData(): array
    {
        return [
            'departments' => Department::ordered()->get(['id', 'name']),
            'fieldTypes' => [
                'text' => 'Single Line Text',
                'textarea' => 'Paragraph',
                'email' => 'Email Address',
                'tel' => 'Phone Number',
                'number' => 'Number',
                'date' => 'Date',
                'select' => 'Dropdown',
                'radio' => 'Radio Buttons',
                'checkbox' => 'Yes / No Toggle',
            ],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['store_submissions'] = $request->boolean('store_submissions', true);
        $data['is_published'] = $request->boolean('is_published', true);

        // Field rows arrive from the builder as flat strings; normalise them
        // into the stored schema here so the renderer never has to parse.
        $rows = collect($data['fields'] ?? [])
            ->filter(fn ($f) => filled($f['label'] ?? null))
            ->map(fn ($f, $i) => [
                'key' => Str::slug($f['label'], '_') ?: 'field_' . ($i + 1),
                'label' => $f['label'],
                'type' => $f['type'] ?? 'text',
                'required' => ! empty($f['required']),
                'help' => $f['help'] ?? null,
                'options' => array_values(array_filter(array_map('trim', explode("\n", (string) ($f['options'] ?? ''))))),
            ])->values()->all();

        $data['fields'] = $rows;

        return $data;
    }
}
