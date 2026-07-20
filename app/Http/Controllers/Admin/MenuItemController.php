<?php

namespace App\Http\Controllers\Admin;

use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MenuItemController extends AdminController
{
    protected string $model = MenuItem::class;
    protected string $views = 'menus';
    protected string $routes = 'menus';
    protected string $label = 'Menu Item';
    protected array $with = ['page', 'children'];
    protected array $searchable = ['label', 'url'];
    protected array $orderBy = ['sort_order', 'asc'];
    protected bool $departmentScoped = false;

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'menu' => ['required', 'in:primary,footer,quicklinks,utility'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'label' => ['required', 'string', 'max:120'],
            'url' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function formData(): array
    {
        return [
            'menus' => [
                'primary' => 'Primary Navigation',
                'quicklinks' => 'Homepage Quick Links',
                'footer' => 'Footer',
                'utility' => 'Top Utility Bar',
            ],
            'pages' => Page::orderBy('title')->get(['id', 'title']),
            'parents' => MenuItem::whereNull('parent_id')->orderBy('menu')->orderBy('label')->get(['id', 'label', 'menu']),
            'icons' => ['home', 'building', 'users', 'book', 'folder', 'clock', 'shield', 'globe', 'bolt', 'edit', 'archive', 'bell'],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['new_tab'] = $request->boolean('new_tab');
        $data['is_published'] = $request->boolean('is_published', true);

        return $data;
    }

    protected function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->isEditor();
    }
}
