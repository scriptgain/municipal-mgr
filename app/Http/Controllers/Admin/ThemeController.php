<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Theme;
use App\Services\Themes\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Theme Manager.
 *
 * Themes only ever change presentation, but they change it for every visitor at
 * once, so this sits behind the admin role alongside the Template Manager.
 */
class ThemeController extends Controller
{
    public function __construct(private ThemeService $themes)
    {
    }

    private function authorise(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Theme management is restricted to administrators.');
    }

    public function index()
    {
        $this->authorise();
        $this->themes->ensurePresets();

        return view('admin.themes.index', [
            'title' => 'Theme Manager',
            'themes' => Theme::orderByDesc('is_active')->orderByDesc('is_preset')->orderBy('name')->get(),
            'defaults' => $this->themes->defaults(),
            'service' => $this->themes,
        ]);
    }

    public function create()
    {
        $this->authorise();

        return view('admin.themes.form', [
            'title' => 'New Theme',
            'theme' => new Theme(['tokens' => []]),
            'tokens' => $this->themes->defaults(),
            'defaults' => $this->themes->defaults(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorise();

        $data = $this->validated($request);

        $theme = Theme::create([
            'name' => $data['name'],
            'slug' => $this->themes->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'tokens' => $this->themes->sanitiseTokens($data['tokens'] ?? []),
            'is_preset' => false,
            'is_active' => false,
            'created_by' => auth()->id(),
        ]);

        AuditLog::record('theme.create', "Created theme {$theme->name}", $theme);

        return redirect()->route('settings.themes.edit', $theme)->with('status', 'Theme Created.');
    }

    public function edit(Theme $theme)
    {
        $this->authorise();

        return view('admin.themes.form', [
            'title' => $theme->name,
            'theme' => $theme,
            'tokens' => $this->themes->tokens($theme),
            'defaults' => $this->themes->defaults(),
        ]);
    }

    public function update(Request $request, Theme $theme)
    {
        $this->authorise();
        abort_unless($theme->isEditable(), 403, 'Shipped presets cannot be edited. Duplicate it first.');

        $data = $this->validated($request);

        $theme->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'tokens' => $this->themes->sanitiseTokens($data['tokens'] ?? []),
        ]);

        AuditLog::record('theme.update', "Updated theme {$theme->name}", $theme);

        return redirect()->route('settings.themes.edit', $theme)->with('status', 'Theme Saved.');
    }

    public function activate(Theme $theme)
    {
        $this->authorise();
        $this->themes->activate($theme);

        AuditLog::record('theme.activate', "Activated theme {$theme->name}", $theme);

        return redirect()->route('settings.themes.index')->with('status', "\"{$theme->name}\" Is Now The Active Theme.");
    }

    public function duplicate(Theme $theme)
    {
        $this->authorise();
        $copy = $this->themes->duplicate($theme, auth()->id());

        AuditLog::record('theme.duplicate', "Duplicated theme {$theme->name} as {$copy->name}", $copy);

        return redirect()->route('settings.themes.edit', $copy)->with('status', 'Theme Duplicated. Edit The Copy Freely.');
    }

    public function destroy(Theme $theme)
    {
        $this->authorise();
        abort_unless($theme->isDeletable(), 403, 'The active theme and shipped presets cannot be deleted.');

        $name = $theme->name;
        $theme->delete();

        AuditLog::record('theme.delete', "Deleted theme {$name}", null);

        return redirect()->route('settings.themes.index')->with('status', 'Theme Deleted.');
    }

    /** massSelect bulk delete, matching every other admin table. */
    public function bulkDestroy(Request $request)
    {
        $this->authorise();

        $ids = collect($request->input('ids', []))->map(fn ($id) => (int) $id)->filter();
        $themes = Theme::whereIn('id', $ids)->get();

        $deleted = 0;
        $skipped = 0;
        foreach ($themes as $theme) {
            if (! $theme->isDeletable()) {
                $skipped++;
                continue;
            }
            $theme->delete();
            $deleted++;
        }

        AuditLog::record('theme.bulk-delete', "Deleted {$deleted} theme(s)", null);

        $message = "Deleted {$deleted} Theme(s).";
        if ($skipped) {
            $message .= " {$skipped} Skipped: The Active Theme And Shipped Presets Are Protected.";
        }

        return redirect()->route('settings.themes.index')->with('status', $message);
    }

    /** Download a theme as JSON so another install can import it. */
    public function export(Theme $theme)
    {
        $this->authorise();

        $payload = $this->themes->export($theme);
        $filename = Str::slug($theme->name) . '-theme.json';

        AuditLog::record('theme.export', "Exported theme {$theme->name}", $theme);

        return response()->streamDownload(
            fn () => print (json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    /** Import from an uploaded file or a pasted JSON blob. */
    public function import(Request $request)
    {
        $this->authorise();

        $request->validate([
            'file' => ['nullable', 'file', 'mimetypes:application/json,text/plain', 'max:512'],
            'json' => ['nullable', 'string', 'max:200000'],
        ]);

        $raw = $request->hasFile('file')
            ? (string) file_get_contents($request->file('file')->getRealPath())
            : (string) $request->input('json');

        if (trim($raw) === '') {
            return back()->with('warning', 'Choose A File Or Paste The Theme JSON.');
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return back()->with('warning', 'That Is Not Valid JSON.');
        }

        try {
            $theme = $this->themes->import($payload, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('warning', $e->getMessage());
        }

        AuditLog::record('theme.import', "Imported theme {$theme->name}", $theme);

        return redirect()->route('settings.themes.edit', $theme)->with('status', 'Theme Imported.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'tokens' => ['nullable', 'array'],
            'tokens.brand' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tokens.accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tokens.chrome' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tokens.chrome_soft' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tokens.font_sans' => ['nullable', 'string', 'max:255'],
            'tokens.font_display' => ['nullable', 'string', 'max:255'],
            'tokens.font_scale' => ['nullable', 'numeric', 'between:0.75,1.5'],
            'tokens.spacing' => ['nullable', 'numeric', 'between:0.75,1.5'],
            'tokens.radius' => ['nullable', 'numeric', 'between:0,3'],
            'tokens.chrome_treatment' => ['nullable', 'in:dark,light'],
            'tokens.logo_url' => ['nullable', 'string', 'max:255'],
            'tokens.favicon_url' => ['nullable', 'string', 'max:255'],
        ], [
            'tokens.*.regex' => 'Colours must be a 6-digit hex value such as #0f4c81.',
        ]);
    }
}
