<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChangelogEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Staff management for the public Release Notes.
 *
 * A thin resource on top of AdminController: it declares what it manages and
 * the base class supplies index paging/search, create/update/delete, and the
 * massSelect bulk delete every admin table gets. Release notes are not scoped
 * by department, so departmentScoped is switched off.
 */
class ChangelogController extends AdminController
{
    protected string $model = ChangelogEntry::class;
    protected string $views = 'changelog';
    protected string $routes = 'changelog';
    protected string $label = 'Changelog Entry';
    protected array $searchable = ['version', 'title', 'summary', 'body'];
    protected array $orderBy = ['released_on', 'desc'];
    protected bool $departmentScoped = false;

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'version' => ['required', 'string', 'max:40'],
            'released_on' => ['required', 'date'],
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
