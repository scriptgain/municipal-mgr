<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends AdminController
{
    protected string $model = Page::class;
    protected string $views = 'pages';
    protected string $routes = 'pages';
    protected string $label = 'Page';
    protected array $with = ['department', 'parent'];
    protected array $searchable = ['title', 'slug', 'summary'];
    protected array $orderBy = ['title', 'asc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'parent_id' => ['nullable', 'integer', 'exists:pages,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'summary' => ['nullable', 'string', 'max:300'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'template' => ['required', 'in:standard,wide,landing'],
            'status' => ['required', 'in:draft,published'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'hero_image_path' => ['nullable', 'string', 'max:255'],
            'sections' => ['nullable', 'array'],
            'sections.*.type' => ['required_with:sections', 'string', 'max:40'],
            'sections.*.heading' => ['nullable', 'string', 'max:200'],
            'sections.*.body' => ['nullable', 'string'],
        ];
    }

    protected function formData(): array
    {
        return [
            'departments' => Department::ordered()->get(['id', 'name']),
            'parents' => Page::orderBy('title')->get(['id', 'title']),
            'sectionTypes' => config('municipal.sections'),
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['show_in_nav'] = $request->boolean('show_in_nav');
        $data['updated_by'] = auth()->id();

        // Publishing stamps a time once; re-saving a published page keeps it.
        if ($data['status'] === 'published' && ! ($record?->published_at)) {
            $data['published_at'] = now();
        }

        // Drop blank section blocks so an empty builder row never renders.
        $data['sections'] = collect($data['sections'] ?? [])
            ->filter(fn ($s) => ! empty($s['type']) && (filled($s['heading'] ?? null) || filled($s['body'] ?? null) || filled($s['items'] ?? null)))
            ->values()->all();

        return $data;
    }

    /** Duplicate a page as a fresh draft — the fastest way to build a sibling. */
    public function duplicate(Page $page)
    {
        $copy = $page->replicate(['slug', 'published_at']);
        $copy->title = $page->title . ' (Copy)';
        $copy->slug = Page::uniqueSlug($copy->title);
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->updated_by = auth()->id();
        $copy->save();

        return redirect()->route('pages.edit', $copy)->with('status', 'Page Duplicated As A Draft.');
    }

    /** One-click publish/unpublish from the index row. */
    public function publish(Request $request, Page $page)
    {
        $this->authorizeRecord($page);
        $publish = $request->boolean('published');
        $page->status = $publish ? 'published' : 'draft';
        $page->published_at = $publish ? ($page->published_at ?: now()) : null;
        $page->save();

        return back()->with('status', 'Page "' . $page->title . '" ' . ($publish ? 'Published.' : 'Unpublished.'));
    }
}
