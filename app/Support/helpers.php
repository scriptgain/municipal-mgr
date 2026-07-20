<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('asset_v')) {
    /**
     * Cache-busted asset URL.
     *
     * Uses the file's LAST-MODIFIED TIME, never time(): a time()-based query
     * string changes on every request, so every visitor re-downloads every CSS
     * and JS file on every page load. filemtime changes only when the file
     * actually changes, which is the entire point of the cache buster.
     */
    function asset_v(string $path): string
    {
        $rel = ltrim($path, '/');
        $abs = public_path($rel);
        $v = is_file($abs) ? filemtime($abs) : null;

        return asset($rel) . ($v ? '?v=' . $v : '');
    }
}

if (! function_exists('municipal_upload_url')) {
    /** Public URL for an uploaded media/document path stored on the public disk. */
    function municipal_upload_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('bulk_state')) {
    /**
     * Alpine x-data payload powering an admin table's massSelect.
     *
     * Returned from a helper rather than written inline in Blade so every admin
     * table selects, clears, and submits identically, and so the view stays
     * markup-only.
     */
    function bulk_state(iterable $ids): string
    {
        $list = collect($ids)->map(fn ($id) => (int) $id)->implode(',');

        return '{'
            . "selected: [], allIds: [{$list}],"
            . 'submitBulk() {'
            . '  const form = this.$refs.bulkForm;'
            . '  form.querySelectorAll(\'input.js-bulk-id\').forEach(node => node.remove());'
            . '  this.selected.forEach(id => {'
            . '    const input = document.createElement(\'input\');'
            . '    input.type = \'hidden\'; input.name = \'ids[]\'; input.value = id; input.className = \'js-bulk-id\';'
            . '    form.appendChild(input);'
            . '  });'
            . '  form.submit();'
            . '}'
            . '}';
    }
}

if (! function_exists('file_bulk_state')) {
    /**
     * Alpine x-data for the File Manager table.
     *
     * Same massSelect contract as bulk_state (selected / allIds / submitBulk)
     * plus submitMove, because files support a second bulk action: moving the
     * selection into another folder. Both actions post the checked ids into a
     * hidden form and both sit behind a modal, never a native dialog.
     */
    function file_bulk_state(iterable $ids): string
    {
        $list = collect($ids)->map(fn ($id) => (int) $id)->implode(',');

        return '{'
            . "selected: [], allIds: [{$list}], moveTarget: '',"
            . 'fill(form) {'
            . '  form.querySelectorAll(\'input.js-bulk-id\').forEach(node => node.remove());'
            . '  this.selected.forEach(id => {'
            . '    const input = document.createElement(\'input\');'
            . '    input.type = \'hidden\'; input.name = \'ids[]\'; input.value = id; input.className = \'js-bulk-id\';'
            . '    form.appendChild(input);'
            . '  });'
            . '},'
            . 'submitBulk() { const f = this.$refs.bulkForm; this.fill(f); f.submit(); },'
            . 'submitMove() { const f = this.$refs.moveForm; this.fill(f); f.submit(); }'
            . '}';
    }
}
