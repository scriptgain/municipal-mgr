<?php

namespace App\Services\Templates;

use App\Models\Setting;
use App\Models\TemplateOverride;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * The override layer that sits IN FRONT OF resources/views.
 *
 * How precedence works
 * --------------------
 * Blade resolves a view name by walking the finder's list of paths and taking
 * the first hit. Prepending this directory therefore makes a stored override
 * shadow the shipped file without touching it, and deleting the row un-shadows
 * it. The DB row stays the source of truth; the file here is a derived cache
 * so that Blade's own compiler, its mtime-based staleness check, and the
 * compiled-view cache all keep working exactly as they do for shipped views.
 *
 * The alternative (a fully custom view engine reading from the DB on every
 * render) would have meant re-implementing compilation and caching, and every
 * bug in that re-implementation would have been a way to take a live
 * government website down. This costs one directory instead.
 */
class TemplateOverrideStore
{
    /** Setting key holding how many overrides exist, so boot can skip a query. */
    public const COUNT_KEY = 'template_overrides_count';

    public function basePath(): string
    {
        return storage_path('app/template-overrides');
    }

    public function previewPath(int $userId): string
    {
        return storage_path('app/template-previews/' . $userId);
    }

    /** Absolute file path an override for $view materialises to. */
    public function pathFor(string $view, ?string $base = null): string
    {
        return ($base ?? $this->basePath()) . '/' . str_replace('.', '/', $view) . '.blade.php';
    }

    public function write(string $view, string $content, ?string $base = null): string
    {
        $path = $this->pathFor($view, $base);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        $this->clearCompiled($path);
        $this->refreshCount();

        return $path;
    }

    public function forget(string $view, ?string $base = null): void
    {
        $path = $this->pathFor($view, $base);
        if (is_file($path)) {
            $this->clearCompiled($path);
            File::delete($path);
        }
        $this->refreshCount();
    }

    /** Rebuild every override file from the DB. Used after a deploy or restore. */
    public function syncAll(): int
    {
        File::ensureDirectoryExists($this->basePath());

        $count = 0;
        foreach (TemplateOverride::all() as $override) {
            $this->write($override->view, $override->content);
            $count++;
        }

        return $count;
    }

    /**
     * Register the override directory with the view finder.
     *
     * Called from AppServiceProvider::boot. The cheap count check means a
     * normal request never queries: the number is carried in the settings map
     * the provider already loaded.
     */
    public function register(array $settings = []): void
    {
        $expected = (int) ($settings[self::COUNT_KEY] ?? 0);

        // Deploys rsync code, not storage. If the DB says overrides exist but
        // the derived files are gone, rebuild them rather than silently
        // serving the shipped template a municipality thought it had replaced.
        if ($expected > 0 && ! is_dir($this->basePath())) {
            rescue(fn () => $this->syncAll(), null, false);
        }

        if (is_dir($this->basePath())) {
            View::prependLocation($this->basePath());
        }
    }

    /**
     * Drop the compiled PHP for one override file.
     *
     * Blade would recompile on mtime anyway, but an override can be written and
     * read inside the same second, and a stale compiled file in that window is
     * exactly the class of bug this feature must not have.
     */
    private function clearCompiled(string $path): void
    {
        rescue(function () use ($path) {
            $compiled = app('blade.compiler')->getCompiledPath($path);
            if ($compiled && is_file($compiled)) {
                File::delete($compiled);
            }
        }, null, false);
    }

    private function refreshCount(): void
    {
        rescue(fn () => Setting::put(self::COUNT_KEY, (string) TemplateOverride::count()), null, false);
    }
}
