<?php

namespace App\Http\Controllers\Admin;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AlertController extends AdminController
{
    protected string $model = Alert::class;
    protected string $views = 'alerts';
    protected string $routes = 'alerts';
    protected string $label = 'Alert';
    protected array $searchable = ['title', 'message'];
    protected array $orderBy = ['created_at', 'desc'];
    protected bool $departmentScoped = false;

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'message' => ['nullable', 'string', 'max:1000'],
            'level' => ['required', 'in:info,advisory,warning,emergency'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'link_label' => ['nullable', 'string', 'max:80'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    protected function formData(): array
    {
        return [
            'levels' => [
                'info' => 'Information',
                'advisory' => 'Advisory',
                'warning' => 'Warning',
                'emergency' => 'Emergency',
            ],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['is_active'] = $request->boolean('is_active', true);
        // An emergency banner is never dismissible: that is the whole point.
        $data['is_dismissible'] = $data['level'] === 'emergency' ? false : $request->boolean('is_dismissible', true);

        return $data;
    }

    /** Alerts are site-wide emergency comms — editors and admins only. */
    protected function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->isEditor();
    }
}
