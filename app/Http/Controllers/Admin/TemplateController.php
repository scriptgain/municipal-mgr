<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TemplatePreview;
use App\Models\AuditLog;
use App\Models\TemplateOverride;
use App\Models\TemplateVersion;
use App\Services\Templates\TemplateCatalog;
use App\Services\Templates\TemplateDiff;
use App\Services\Templates\TemplateOverrideStore;
use App\Services\Templates\TemplateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

/**
 * Template Manager.
 *
 * Staff edit real Blade here, with real consequences, so the guarantees are:
 *
 *   - ADMIN ONLY. Editing a template is equivalent to executing code on the
 *     server. Being logged in is not enough; only the admin role gets here.
 *   - NOTHING INVALID IS EVER PERSISTED. Every write path (save, revert,
 *     import of a preview draft) runs TemplateValidator first and returns the
 *     parse error to the operator instead of storing the template.
 *   - THE SHIPPED FILE IS NEVER TOUCHED. Overrides live in the database, so
 *     "reset to shipped default" is a delete and a signed release can still
 *     land cleanly on a customised install.
 */
class TemplateController extends Controller
{
    public function __construct(
        private TemplateCatalog $catalog,
        private TemplateValidator $validator,
        private TemplateOverrideStore $store,
        private TemplateDiff $diff,
    ) {
    }

    /** Every entry point re-checks the role. There is no "mostly admin" here. */
    private function authorise(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Template editing is restricted to administrators.');
    }

    private function guardView(string $view): void
    {
        abort_unless($this->catalog->isEditable($view), 404, 'That template is not editable.');
    }

    public function index(Request $request)
    {
        $this->authorise();

        $groups = $this->catalog->groups();
        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            foreach ($groups as $id => $group) {
                $groups[$id]['items'] = array_values(array_filter(
                    $group['items'],
                    fn ($item) => str_contains(strtolower($item['view']), strtolower($search))
                ));
                if (! $groups[$id]['items']) {
                    unset($groups[$id]);
                }
            }
        }

        $overriddenTotal = TemplateOverride::count();

        // Tab strip built here, not in the template: the view renders markup.
        $tabs = [];
        foreach ($groups as $id => $group) {
            $tabs[$id] = [
                'label' => $group['label'],
                'icon' => $group['icon'],
                'count' => count($group['items']),
            ];
        }

        return view('admin.templates.index', [
            'title' => 'Template Manager',
            'groups' => $groups,
            'tabs' => $tabs,
            'search' => $search,
            'overriddenTotal' => $overriddenTotal,
            'templateTotal' => count($this->catalog->views()),
        ]);
    }

    public function edit(Request $request, string $view)
    {
        $this->authorise();
        $this->guardView($view);

        $override = TemplateOverride::where('view', $view)->first();
        $shipped = $this->catalog->shippedSource($view);
        $current = $override->content ?? (string) $shipped;

        $versions = TemplateVersion::where('view', $view)
            ->with('user')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('admin.templates.edit', [
            'title' => $this->catalog->label($view),
            'view' => $view,
            'label' => $this->catalog->label($view),
            'path' => 'resources/views/' . str_replace('.', '/', $view) . '.blade.php',
            'override' => $override,
            'shipped' => (string) $shipped,
            'content' => old('content', $current),
            'versions' => $versions,
            'previewUrl' => $this->catalog->previewUrl($view),
            'shippedDiff' => $override ? $this->diff->compare((string) $shipped, $override->content) : null,
            'lineCount' => substr_count($current, "\n") + 1,
        ]);
    }

    /**
     * Save an override.
     *
     * The validation gate is the entire point of this method. A template that
     * does not survive it never reaches the database, never reaches the view
     * finder, and cannot take the site down.
     */
    public function update(Request $request, string $view)
    {
        $this->authorise();
        $this->guardView($view);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:500000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($error = $this->validator->validate($data['content'])) {
            return back()
                ->withInput()
                ->with('template_error', $error)
                ->with('warning', 'Template Not Saved. ' . $this->summarise($error));
        }

        $override = TemplateOverride::updateOrCreate(
            ['view' => $view],
            ['content' => $data['content'], 'updated_by' => auth()->id()]
        );

        $this->snapshot($view, $data['content'], 'save', $data['note'] ?? null);
        $this->clearPreview($request);

        AuditLog::record('template.save', "Saved template override for {$view}", $override);

        return redirect()
            ->route('settings.templates.edit', $view)
            ->with('status', 'Template Saved. The override is live.');
    }

    /** Revert to a stored version. Re-validated: history is not trusted blindly. */
    public function revert(Request $request, string $view, TemplateVersion $version)
    {
        $this->authorise();
        $this->guardView($view);
        abort_unless($version->view === $view, 404);

        if ($error = $this->validator->validate($version->content)) {
            return back()->with('template_error', $error)
                ->with('warning', 'Revert Refused. That version no longer compiles: ' . $this->summarise($error));
        }

        $override = TemplateOverride::updateOrCreate(
            ['view' => $view],
            ['content' => $version->content, 'updated_by' => auth()->id()]
        );

        $this->snapshot($view, $version->content, 'revert', 'Reverted to version #' . $version->id);
        $this->clearPreview($request);

        AuditLog::record('template.revert', "Reverted template {$view} to version #{$version->id}", $override);

        return redirect()
            ->route('settings.templates.edit', $view)
            ->with('status', 'Reverted To Version #' . $version->id . '.');
    }

    /** Drop the override entirely and fall back to the shipped file. */
    public function reset(Request $request, string $view)
    {
        $this->authorise();
        $this->guardView($view);

        $override = TemplateOverride::where('view', $view)->first();

        if ($override) {
            // Snapshot what is being discarded first, so "reset" is undoable.
            $this->snapshot($view, $override->content, 'reset', 'Content at reset to shipped default');
            $override->delete();
            AuditLog::record('template.reset', "Reset template {$view} to the shipped default", null);
        }

        $this->clearPreview($request);

        return redirect()
            ->route('settings.templates.edit', $view)
            ->with('status', 'Reset To The Shipped Default. The override has been removed.');
    }

    /**
     * Stage the editor's current content as a live preview.
     *
     * Validated exactly as a save is, then written to a per-user directory the
     * TemplatePreview middleware puts in front of the published override. Only
     * this admin's session sees it, and only for ten minutes.
     */
    public function preview(Request $request, string $view)
    {
        $this->authorise();
        $this->guardView($view);

        $data = $request->validate(['content' => ['required', 'string', 'max:500000']]);

        if ($error = $this->validator->validate($data['content'])) {
            return back()
                ->withInput()
                ->with('template_error', $error)
                ->with('warning', 'Preview Not Started. ' . $this->summarise($error));
        }

        $dir = $this->store->previewPath((int) auth()->id());
        File::deleteDirectory($dir);
        $this->store->write($view, $data['content'], $dir);

        $request->session()->put(TemplatePreview::SESSION_KEY, [
            'user_id' => auth()->id(),
            'view' => $view,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        AuditLog::record('template.preview', "Started a template preview for {$view}", null);

        return redirect()
            ->route('settings.templates.edit', $view)
            ->with('status', 'Preview Started. It Is Visible Only To You And Expires In 10 Minutes.');
    }

    public function stopPreview(Request $request)
    {
        $this->authorise();
        $view = $request->session()->get(TemplatePreview::SESSION_KEY)['view'] ?? null;
        $this->clearPreview($request);

        return back()->with('status', 'Preview Stopped.');
    }

    /** The compiled PHP, shown in the editor so Blade stops being a black box. */
    public function compiled(Request $request, string $view)
    {
        $this->authorise();
        $this->guardView($view);

        $source = $request->input('content') ?? $this->catalog->effectiveSource($view);

        if ($error = $this->validator->validate($source)) {
            return response()->json(['ok' => false, 'error' => $error]);
        }

        return response()->json([
            'ok' => true,
            'compiled' => Blade::compileString($source),
        ]);
    }

    /** Live syntax check for the editor, same gate the save uses. */
    public function check(Request $request, string $view)
    {
        $this->authorise();
        $this->guardView($view);

        $error = $this->validator->validate((string) $request->input('content', ''));

        return response()->json([
            'ok' => $error === null,
            'error' => $error,
            'summary' => $error ? $this->summarise($error) : 'No syntax errors.',
        ]);
    }

    public function diff(Request $request, string $view, TemplateVersion $version)
    {
        $this->authorise();
        $this->guardView($view);
        abort_unless($version->view === $view, 404);

        return view('admin.templates.diff', [
            'title' => 'Compare Version #' . $version->id,
            'view' => $view,
            'label' => $this->catalog->label($view),
            'version' => $version,
            'diff' => $this->diff->compare($version->content, $this->catalog->effectiveSource($view)),
        ]);
    }

    private function snapshot(string $view, string $content, string $action, ?string $note): void
    {
        TemplateVersion::create([
            'view' => $view,
            'content' => $content,
            'action' => $action,
            'note' => $note,
            'user_id' => auth()->id(),
        ]);
    }

    private function clearPreview(Request $request): void
    {
        $request->session()->forget(TemplatePreview::SESSION_KEY);
        rescue(fn () => File::deleteDirectory($this->store->previewPath((int) auth()->id())), null, false);
    }

    private function summarise(array $error): string
    {
        return $error['line']
            ? $error['message'] . ' (line ' . $error['line'] . ')'
            : $error['message'];
    }
}
