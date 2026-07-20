@props(['tree' => [], 'active' => null, 'rootLabel' => 'All Files', 'rootHref' => null])
{{-- Keyboard-operable folder tree.

     Real links inside role="tree"/"treeitem" so it works with Tab and with a
     screen reader out of the box; public/js/municipal.js adds arrow-key
     roving focus on top (see the mm-folder-tree handler). Indentation comes
     from a precomputed `indent` value so this template stays markup only. --}}
<nav aria-label="Folders" class="mm-folder-tree" role="tree">
    <a href="{{ $rootHref }}" role="treeitem" aria-level="1"
       @if (! $active) aria-current="page" @endif
       @class([
           'group flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
           'bg-brand-50 font-semibold text-brand-800' => ! $active,
           'text-slate-700 hover:bg-slate-100' => (bool) $active,
       ])>
        <x-icon name="archive" class="w-4 h-4 shrink-0 text-slate-400" />
        <span class="truncate">{{ $rootLabel }}</span>
    </a>

    @foreach ($tree as $node)
        <a href="{{ route('files.index', ['folder' => $node['folder']->slug]) }}"
           role="treeitem" aria-level="{{ $node['depth'] + 2 }}"
           style="padding-left: {{ $node['indent'] }}px"
           @if ($active === $node['folder']->slug) aria-current="page" @endif
           @class([
               'group flex items-center gap-2 rounded-lg py-2 pr-2.5 text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
               'bg-brand-50 font-semibold text-brand-800' => $active === $node['folder']->slug,
               'text-slate-700 hover:bg-slate-100' => $active !== $node['folder']->slug,
           ])>
            <x-icon :name="$node['folder']->icon ?: 'folder'" class="w-4 h-4 shrink-0 text-slate-400 ml-2.5" />
            <span class="truncate">{{ $node['folder']->name }}</span>
            @unless ($node['folder']->is_public)
                <span class="ml-auto shrink-0" data-tip="Staff Only. This folder is hidden from the public file browser.">
                    <x-icon name="lock" class="w-3.5 h-3.5 text-amber-500" />
                    <span class="sr-only">Staff Only</span>
                </span>
            @endunless
        </a>
    @endforeach
</nav>
