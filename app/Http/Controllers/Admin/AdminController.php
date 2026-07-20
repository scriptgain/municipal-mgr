<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shared behaviour for every admin resource screen.
 *
 * The concrete controllers declare WHAT they manage (model, views, rules,
 * eager loads, search columns) and this class supplies HOW — index paging and
 * search, create/update/delete, department scoping for department editors, and
 * the massSelect bulk delete every admin table gets.
 *
 * Keeping it here means a new content type is a ~40 line controller plus its
 * views, and every table behaves identically for staff who use several of them.
 */
abstract class AdminController extends Controller
{
    /** Eloquent model class this controller manages. */
    protected string $model;

    /** View directory under resources/views/admin (e.g. 'news'). */
    protected string $views;

    /** Route name prefix (e.g. 'news' -> news.index). */
    protected string $routes;

    /** Singular human label used in flash messages ("News Post"). */
    protected string $label = 'Record';

    /** Relations eager-loaded on the index screen. */
    protected array $with = [];

    /** Columns searched by the index search box. */
    protected array $searchable = ['title'];

    /** Default index ordering: [column, direction]. */
    protected array $orderBy = ['created_at', 'desc'];

    /** Whether rows are scoped by department for department editors. */
    protected bool $departmentScoped = true;

    /** Validation rules for store/update. */
    abstract protected function rules(Request $request, ?Model $record = null): array;

    /** Extra data every form screen needs (departments, categories, ...). */
    protected function formData(): array
    {
        return [];
    }

    /** Hook: adjust validated data before it is written. */
    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        return $data;
    }

    /** Hook: run after a record is created or updated (file handling, etc). */
    protected function afterSave(Model $record, Request $request): void
    {
        //
    }

    public function index(Request $request)
    {
        $query = $this->scoped()->with($this->with);

        if ($term = trim((string) $request->query('q'))) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
            $query->where(function (Builder $q) use ($like) {
                foreach ($this->searchable as $i => $col) {
                    $i === 0 ? $q->where($col, 'like', $like) : $q->orWhere($col, 'like', $like);
                }
            });
        }

        $records = $query
            ->orderBy($this->orderBy[0], $this->orderBy[1] ?? 'asc')
            ->paginate((int) config('municipal.rows_per_page', 25))
            ->withQueryString();

        return view("admin.{$this->views}.index", [
            'records' => $records,
            'search' => $request->query('q'),
        ] + $this->formData());
    }

    public function create()
    {
        return view("admin.{$this->views}.create", [
            'record' => new $this->model,
        ] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->transform($request->validate($this->rules($request)), $request);
        $record = new $this->model;
        $record->fill($data);
        $this->applyDepartmentDefault($record);
        $record->save();
        $this->afterSave($record, $request);

        return redirect()->route("{$this->routes}.index")
            ->with('status', "{$this->label} \"{$this->title($record)}\" Created.");
    }

    public function show(string $key)
    {
        return redirect()->route("{$this->routes}.edit", $key);
    }

    public function edit(Request $request, string $key)
    {
        $record = $this->findOrFail($key);
        $this->authorizeRecord($record);

        return view("admin.{$this->views}.edit", [
            'record' => $record,
        ] + $this->formData());
    }

    public function update(Request $request, string $key)
    {
        $record = $this->findOrFail($key);
        $this->authorizeRecord($record);

        $data = $this->transform($request->validate($this->rules($request, $record)), $request, $record);
        $record->fill($data);
        $record->save();
        $this->afterSave($record, $request);

        return redirect()->route("{$this->routes}.index")
            ->with('status', "{$this->label} \"{$this->title($record)}\" Saved.");
    }

    public function destroy(string $key)
    {
        $record = $this->findOrFail($key);
        $this->authorizeRecord($record);
        $title = $this->title($record);
        $record->delete();

        return redirect()->route("{$this->routes}.index")
            ->with('status', "{$this->label} \"{$title}\" Deleted.");
    }

    /**
     * massSelect bulk delete. Every admin table posts its checked ids here
     * behind a modal confirm — there is no native confirm() anywhere.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if (! $ids) {
            return back()->with('warning', 'No Rows Were Selected.');
        }

        $records = $this->scoped()->whereIn('id', $ids)->get();
        $n = 0;
        foreach ($records as $record) {
            if ($this->canEdit($record)) {
                $record->delete();
                $n++;
            }
        }

        AuditLog::record('bulk-deleted', "{$n} {$this->label} record(s) deleted in bulk");

        return back()->with('status', "{$n} {$this->label} Record(s) Deleted.");
    }

    /* ---------------------------------------------------------------- */

    /** Base query, narrowed to what the signed-in user may manage. */
    protected function scoped(): Builder
    {
        $query = $this->model::query();
        $user = auth()->user();

        if ($this->departmentScoped
            && $user
            && $user->isDepartmentEditor()
            && $this->hasDepartmentColumn()) {
            $query->where('department_id', $user->department_id);
        }

        return $query;
    }

    protected function findOrFail(string $key): Model
    {
        $instance = new $this->model;
        $field = $instance->getRouteKeyName();

        return $this->model::where($field, $key)->firstOrFail();
    }

    protected function hasDepartmentColumn(): bool
    {
        return in_array('department_id', (new $this->model)->getFillable(), true);
    }

    protected function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->canEditContent()) {
            return false;
        }
        if (! $this->departmentScoped || ! $this->hasDepartmentColumn()) {
            return $user->isEditor() || $user->isDepartmentEditor();
        }

        return $user->canEditDepartment($record->department_id);
    }

    protected function authorizeRecord(Model $record): void
    {
        abort_unless($this->canEdit($record), 403, 'You May Only Edit Your Own Department\'s Content.');
    }

    /** A department editor's new records default to their own department. */
    protected function applyDepartmentDefault(Model $record): void
    {
        $user = auth()->user();
        if ($user && $user->isDepartmentEditor() && $this->hasDepartmentColumn() && ! $record->department_id) {
            $record->department_id = $user->department_id;
        }
    }

    protected function title(Model $record): string
    {
        return (string) ($record->title ?? $record->name ?? $record->reference ?? $record->getKey());
    }

    /**
     * Store an uploaded file on the public disk and return its path, keeping
     * the original name readable in the URL (residents share these links).
     */
    protected function storeUpload(Request $request, string $field, string $dir): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }
        $file = $request->file($field);
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . Str::lower(Str::random(6)) . '.' . $file->getClientOriginalExtension();

        return Storage::disk('public')->putFileAs($dir, $file, $name);
    }
}
