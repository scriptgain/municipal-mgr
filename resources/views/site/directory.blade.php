<x-layouts.public title="Staff Directory">
    <x-site.page-hero title="Staff Directory"
                      subtitle="Reach the right person directly, without going through a phone tree."
                      :crumbs="[['label' => 'Staff Directory']]" />

    <x-site.section :divider="false">
        <form method="GET" class="mb-8 flex flex-wrap items-end gap-3 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
            <div class="flex-1 min-w-[14rem]">
                <label for="q" class="block text-sm font-medium text-slate-700">Search By Name Or Title</label>
                <input id="q" name="q" type="search" value="{{ $search }}"
                       class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
            </div>
            <div>
                <label for="department" class="block text-sm font-medium text-slate-700">Department</label>
                <select id="department" name="department" data-auto-submit
                        class="mt-1.5 rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->slug }}" @selected($activeDepartment === $department->slug)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Search</button>
            @if ($search || $activeDepartment)
                <a href="{{ route('site.directory') }}" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-white transition">Clear</a>
            @endif
        </form>

        @if ($staff->count())
            <div class="overflow-x-auto mm-scroll rounded-2xl ring-1 ring-slate-200 bg-white">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Municipal staff directory</caption>
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Title</th>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</th>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($staff as $member)
                            <tr class="hover:bg-brand-50/40">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-avatar size="sm" :initials="$member->initials()" :name="$member->name"
                                                  :src="$member->photo_path ? municipal_upload_url($member->photo_path) : null" />
                                        <span class="font-medium text-slate-900">{{ $member->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $member->job_title }}</td>
                                <td class="px-5 py-4">
                                    @if ($member->department)
                                        <a href="{{ route('site.departments.show', $member->department->slug) }}" class="text-brand-700 hover:underline">{{ $member->department->name }}</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 tabular text-slate-600">
                                    @if ($member->phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $member->phone) }}" class="hover:text-brand-700 hover:underline">{{ $member->phoneDisplay() }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($member->email)
                                        <a href="mailto:{{ $member->email }}" class="text-brand-700 hover:underline">{{ $member->email }}</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-site.empty title="No Staff Match That Search" icon="users"
                          message="Try a different name, or browse by department." />
        @endif
    </x-site.section>
</x-layouts.public>
