@props(['record', 'kind' => 'Page'])
{{-- Search Appearance panel, shared by every editable content type.

     Collapsed by default on purpose: an editor writing a news post should not
     have to scroll past five optional fields to reach Save. Everything in here
     is optional, and the hint under each field says what happens when it is
     left blank, because the honest answer is "the right thing".

     Markup only. Placeholders and counters are wired by public/js/seo.js, and
     every fallback value is computed by the model's HasSeo trait. --}}
<div x-data="{ open: false }" class="bg-white rounded-xl ring-1 ring-slate-200 shadow-sm">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()"
            class="flex w-full items-center gap-3 px-5 sm:px-6 py-4 text-left">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
            <x-icon name="search" class="w-4 h-4" />
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-[15px] font-semibold text-slate-900">Search Appearance</span>
            <span class="block text-sm text-slate-500">
                How This {{ $kind }} Looks On Google And When Shared. Optional.
            </span>
        </span>
        @if ($record->noindex)
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                <x-icon name="warning" class="w-3.5 h-3.5" /> Hidden From Search
            </span>
        @endif
        <x-icon name="chevron-down" class="w-5 h-5 shrink-0 text-slate-400 transition-transform" ::class="open && 'rotate-180'" />
    </button>

    <div x-show="open" x-cloak class="border-t border-slate-100 px-5 sm:px-6 py-5" data-seo-panel>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Search Engine Title" for="meta_title" class="sm:col-span-2"
                     hint="Leave blank to use the title above. Around 60 characters shows in full."
                     :error="$errors->first('meta_title')">
                <x-input id="meta_title" name="meta_title" maxlength="255"
                         data-seo-title
                         data-seo-fallback="{{ $record->seoDefaultTitle() }}"
                         :value="old('meta_title', $record->meta_title)" />
                <p class="mt-1.5 text-xs text-slate-500" data-seo-count-for="meta_title" aria-live="polite"></p>
            </x-field>

            <x-field label="Meta Description" for="meta_description" class="sm:col-span-2"
                     hint="Leave blank and this is written from the content. Around 155 characters shows in full."
                     :error="$errors->first('meta_description')">
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="500"
                          data-seo-description
                          data-seo-fallback="{{ $record->seoDerivedDescription() }}"
                          class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('meta_description', $record->meta_description) }}</textarea>
                <p class="mt-1.5 text-xs text-slate-500" data-seo-count-for="meta_description" aria-live="polite"></p>
            </x-field>

            {{-- Live result preview. Built from the two fields above so an
                 editor can see the truncation before it happens. --}}
            <div class="sm:col-span-2">
                <p class="mb-2 text-sm font-medium text-slate-700">Search Result Preview</p>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                    <p class="truncate text-xs text-slate-500" data-seo-preview-url>{{ $record->seoUrl() ?? url('/') }}</p>
                    <p class="mt-1 truncate text-lg text-[#1a0dab]" data-seo-preview-title></p>
                    <p class="mt-1 text-sm leading-snug text-slate-600" data-seo-preview-description></p>
                </div>
            </div>

            <x-field label="Social Share Image" for="og_image_file"
                     hint="Used when this is shared on Facebook, X, or in a group chat. 1200 by 630 works best."
                     :error="$errors->first('og_image_file')">
                <input id="og_image_file" name="og_image_file" type="file" accept="image/*"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                @if ($record->og_image)
                    <img src="{{ municipal_upload_url($record->og_image) }}" alt="Current social share image"
                         class="mt-3 h-24 rounded-lg object-cover ring-1 ring-slate-200">
                @endif
            </x-field>

            <x-field label="Canonical URL" for="canonical_url"
                     hint="Only set this when the same content lives at another address. Blank is almost always right."
                     :error="$errors->first('canonical_url')">
                <x-input id="canonical_url" name="canonical_url" type="url" maxlength="255"
                         placeholder="https://" :value="old('canonical_url', $record->canonical_url)" />
            </x-field>

            <div class="sm:col-span-2 section-divider pt-5">
                <x-toggle name="noindex" :checked="old('noindex', $record->noindex)"
                          label="Hide From Search Engines"
                          description="Keeps this {{ strtolower($kind) }} on the site and reachable by link, but out of Google and out of sitemap.xml." />
            </div>
        </div>
    </div>
</div>
