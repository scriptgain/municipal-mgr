<?php

namespace App\Services\Templates;

use Illuminate\Support\Facades\Blade;

/**
 * The safety net. Nothing reaches the override store without clearing this.
 *
 * Why not view:cache
 * ------------------
 * Because in this app `view:cache` reports success on templates that compile
 * to INVALID PHP. It has bitten twice. The artisan command only asks the Blade
 * compiler to emit a file; nobody ever asks PHP whether the emitted file
 * parses. So the check here is done directly, in two stages:
 *
 *   1. Blade::compileString() - catches malformed directives.
 *   2. token_get_all($compiled, TOKEN_PARSE) - hands the COMPILED PHP to the
 *      real PHP parser. With TOKEN_PARSE the tokenizer performs a full parse
 *      and throws \ParseError on invalid syntax, WITHOUT EXECUTING ANY OF IT.
 *
 * eval() would also surface the error, and would also run whatever the
 * template does on the way there. It is never used here.
 */
class TemplateValidator
{
    /**
     * @return array{message:string,line:?int,stage:string,snippet:array}|null
     *         null when the template is safe to persist.
     */
    public function validate(string $source): ?array
    {
        if ($problem = $this->guardFirstLinePhp($source)) {
            return $problem;
        }

        try {
            $compiled = Blade::compileString($source);
        } catch (\Throwable $e) {
            return [
                'stage' => 'blade',
                'message' => 'Blade could not compile this template: ' . $e->getMessage(),
                'line' => null,
                'snippet' => [],
            ];
        }

        try {
            // Parses. Does not execute. This is the whole safety net.
            token_get_all($compiled, TOKEN_PARSE);
        } catch (\ParseError $e) {
            return [
                'stage' => 'php',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'snippet' => $this->snippet($compiled, $e->getLine()),
            ];
        } catch (\Throwable $e) {
            return [
                'stage' => 'php',
                'message' => get_class($e) . ': ' . $e->getMessage(),
                'line' => null,
                'snippet' => [],
            ];
        }

        return null;
    }

    /**
     * Refuse an @php block on the first line.
     *
     * Blade's raw-block regex is greedy enough that a leading @php swallows
     * everything up to a later @endphp, and the view 500s. It is a real trap in
     * this codebase, it looks harmless, and the resulting error points nowhere
     * near the cause, so it is rejected by name rather than left to confuse
     * whoever hits it.
     */
    private function guardFirstLinePhp(string $source): ?array
    {
        $first = strtok(str_replace(["\r\n", "\r"], "\n", $source), "\n");

        if ($first !== false && preg_match('/^\s*@php\b/', $first)) {
            return [
                'stage' => 'blade',
                'message' => 'Line 1 cannot start with @php. Blade mis-parses a raw PHP block in the first line of a view and the page fails at render time. '
                    . 'Move the block below the first line, or better, move the logic into a view composer or component class.',
                'line' => 1,
                'snippet' => [['line' => 1, 'text' => $first]],
            ];
        }

        return null;
    }

    /** A few lines of the compiled PHP either side of the failure. */
    private function snippet(string $compiled, ?int $line, int $pad = 4): array
    {
        if (! $line) {
            return [];
        }

        $lines = preg_split('/\R/', $compiled) ?: [];
        $out = [];

        for ($i = max(1, $line - $pad); $i <= min(count($lines), $line + $pad); $i++) {
            $out[] = [
                'line' => $i,
                'text' => rtrim($lines[$i - 1] ?? ''),
                'is_error' => $i === $line,
            ];
        }

        return $out;
    }
}
