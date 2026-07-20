<x-layouts.app title="Backup And Restore">
    <x-page-header title="Backup And Restore" icon="archive"
                   subtitle="Export this install's configuration, or take a full database restore point." />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Configuration Snapshot" subtitle="Every DB-backed setting, as JSON. Restores onto any install.">
            <div class="flex flex-wrap gap-2">
                <x-button icon="download" :href="route('settings.backup.config')">Download Configuration</x-button>
            </div>

            <form method="POST" action="{{ route('settings.backup.restore') }}" enctype="multipart/form-data" class="section-divider mt-6 space-y-4 pt-5">
                @csrf
                <x-field label="Restore From A Snapshot" for="backup" hint="Overwrites current settings with the values in the file." :error="$errors->first('backup')">
                    <input id="backup" name="backup" type="file" accept="application/json" required
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                </x-field>
                <x-button type="submit" variant="secondary" icon="upload">Restore Configuration</x-button>
            </form>
        </x-card>

        <x-card title="Full Database Backup" subtitle="A complete restore point, including all content and uploads metadata.">
            <p class="text-sm text-slate-600">
                Downloads a compressed SQL dump of the entire database. Keep these off the server —
                a backup stored only on the machine it protects is not a backup.
            </p>
            <div class="mt-5">
                <x-button icon="database" :href="route('settings.backup.database')">Download Database Dump</x-button>
            </div>
        </x-card>
    </div>
</x-layouts.app>
