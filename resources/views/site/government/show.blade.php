<x-layouts.public :title="$official->name">
    <x-site.page-hero :title="$official->name" :eyebrow="$official->office" :subtitle="$official->district"
                      :crumbs="[['label' => 'Government', 'href' => route('site.government')], ['label' => $official->name]]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                <div class="prose-civic">{!! $official->bio !!}</div>
            </div>
            <aside>
                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <x-avatar size="xl" :initials="$official->initials()" :name="$official->name"
                              :src="$official->photo_path ? municipal_upload_url($official->photo_path) : null" />
                    <h2 class="mt-4 font-semibold text-slate-900">Contact</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">Office</dt>
                            <dd class="text-slate-900">{{ $official->office }}</dd>
                        </div>
                        @if ($official->termDisplay())
                            <div>
                                <dt class="font-medium text-slate-500">Term</dt>
                                <dd class="text-slate-900">{{ $official->termDisplay() }}</dd>
                            </div>
                        @endif
                        @if ($official->email)
                            <div>
                                <dt class="font-medium text-slate-500">Email</dt>
                                <dd><a href="mailto:{{ $official->email }}" class="break-all text-brand-700 hover:underline">{{ $official->email }}</a></dd>
                            </div>
                        @endif
                        @if ($official->phone)
                            <div>
                                <dt class="font-medium text-slate-500">Phone</dt>
                                <dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $official->phone) }}" class="text-brand-700 hover:underline">{{ $official->phone }}</a></dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
