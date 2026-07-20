<x-layouts.app title="Settings">
    <x-page-header title="Settings" icon="settings" subtitle="Site identity, staff accounts, security, and system maintenance." />

    <x-card title="Where To Start">
        <div class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('settings.site.edit') }}" class="flex items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-brand-50 hover:ring-brand-200 transition">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                    <x-icon name="building" class="w-5 h-5" />
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-slate-900">Site Identity</span>
                    <span class="block text-sm text-slate-500">Name, seal, contact details, and the homepage hero.</span>
                </span>
            </a>
            <a href="{{ route('settings.users.index') }}" class="flex items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-brand-50 hover:ring-brand-200 transition">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                    <x-icon name="users" class="w-5 h-5" />
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-slate-900">Users And Roles</span>
                    <span class="block text-sm text-slate-500">Give each department an editor without giving away the whole site.</span>
                </span>
            </a>
            <a href="{{ route('settings.seo.edit') }}" class="flex items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-brand-50 hover:ring-brand-200 transition">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                    <x-icon name="search" class="w-5 h-5" />
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-slate-900">SEO</span>
                    <span class="block text-sm text-slate-500">How residents find this site in search, plus the staging switch.</span>
                </span>
            </a>
            <a href="{{ route('settings.branding.edit') }}" class="flex items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-brand-50 hover:ring-brand-200 transition">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                    <x-icon name="edit" class="w-5 h-5" />
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-slate-900">Branding</span>
                    <span class="block text-sm text-slate-500">Accent colour and panel naming.</span>
                </span>
            </a>
            @if (auth()->user()?->isAdmin())
            <a href="{{ route('settings.themes.index') }}" class="flex items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-brand-50 hover:ring-brand-200 transition">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                    <x-icon name="star" class="w-5 h-5" />
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-slate-900">Theme Manager</span>
                    <span class="block text-sm text-slate-500">Colours, type, and shape. Preview a look before you commit to it.</span>
                </span>
            </a>
            <a href="{{ route('settings.templates.index') }}" class="flex items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-brand-50 hover:ring-brand-200 transition">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                    <x-icon name="edit" class="w-5 h-5" />
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-slate-900">Template Manager</span>
                    <span class="block text-sm text-slate-500">Edit the real Blade behind any page, safely, with full version history.</span>
                </span>
            </a>
            @endif
            <a href="{{ route('settings.license.edit') }}" class="flex items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-brand-50 hover:ring-brand-200 transition">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-200">
                    <x-icon name="shield" class="w-5 h-5" />
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-slate-900">License</span>
                    <span class="block text-sm text-slate-500">Your ScriptGain license key and update channel.</span>
                </span>
            </a>
        </div>
    </x-card>
</x-layouts.app>
