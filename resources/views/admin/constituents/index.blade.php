<x-layouts.app title="Residents">
    <x-page-header title="Residents" icon="users"
                   subtitle="Every person the town has a record of, and everything they have filed.">
        <x-slot:actions>
            <x-button :href="route('constituents.create')" icon="plus">Add A Resident</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="On File" :value="$stats['total']" icon="users" />
        <x-stat label="Active Last 30 Days" :value="$stats['recent']" icon="clock" />
        <x-stat label="No Filings Yet" :value="$stats['unlinked']" icon="clipboard" />
        <x-stat label="Do Not Contact" :value="$stats['flagged']" icon="shield" />
    </div>

    <div class="section-divider my-6"></div>

    <x-card flush>
        <div x-data="{{ bulk_state($records->pluck('id')) }}">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <label class="sr-only" for="q">Search Residents</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-slate-400" />
                        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Name, email, phone, or address…"
                               class="w-72 rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    </div>

                    <label class="sr-only" for="filter">Filter</label>
                    <select id="filter" name="filter" data-auto-submit
                            class="rounded-lg border-0 py-2 pl-3 pr-9 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        <option value="all" @selected($filter === 'all')>Everyone On File</option>
                        <option value="recent" @selected($filter === 'recent')>Active Last 30 Days</option>
                        <option value="unlinked" @selected($filter === 'unlinked')>No Filings Yet</option>
                        <option value="flagged" @selected($filter === 'flagged')>Do Not Contact</option>
                    </select>

                    @if (count($tags))
                        <label class="sr-only" for="tag">Tag</label>
                        <select id="tag" name="tag" data-auto-submit
                                class="rounded-lg border-0 py-2 pl-3 pr-9 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            <option value="">All Tags</option>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag }}" @selected(request('tag') === $tag)>{{ $tag }}</option>
                            @endforeach
                        </select>
                    @endif

                    <label class="sr-only" for="sort">Sort</label>
                    <select id="sort" name="sort" data-auto-submit
                            class="rounded-lg border-0 py-2 pl-3 pr-9 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                        <option value="recent" @selected($sort === 'recent')>Most Recent Contact</option>
                        <option value="name" @selected($sort === 'name')>Name (A To Z)</option>
                        <option value="activity" @selected($sort === 'activity')>Most Service Requests</option>
                    </select>

                    <x-button type="submit" variant="secondary" size="sm">Filter</x-button>
                    @if ($search || request('tag') || $filter !== 'all')
                        <x-button variant="ghost" size="sm" :href="route('constituents.index')">Clear</x-button>
                    @endif
                </form>

                <p class="text-xs text-slate-500">
                    Staff Only. This List Is Never Shown On The Public Site.
                </p>
            </div>

            <x-bulk-bar :action="route('constituents.bulk-destroy')" label="Resident" modal="bulk-delete-constituents" />

            @if ($records->count())
                <x-table flush>
                    <caption class="sr-only">Residents On File</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="w-10"><x-select-all /></th>
                            <th scope="col">Resident</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Address</th>
                            <th scope="col">Activity</th>
                            <th scope="col">Last Contact</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $person)
                            <tr>
                                <td><x-select-row :id="$person->id" :label="$person->name" /></td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar size="sm" :initials="$person->initials()" :name="$person->name" />
                                        <div class="min-w-0">
                                            <a href="{{ route('constituents.show', $person) }}"
                                               class="block truncate font-medium text-slate-900 hover:text-brand-700">{{ $person->name }}</a>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                                @if ($person->do_not_contact)
                                                    <x-badge color="danger" dot>Do Not Contact</x-badge>
                                                @endif
                                                @foreach ($person->tagList() as $tag)
                                                    <x-badge color="neutral">{{ $tag }}</x-badge>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-slate-600">
                                    @if ($person->email)
                                        <span class="block truncate">{{ $person->email }}</span>
                                    @endif
                                    @if ($person->phone)
                                        <span class="block truncate text-slate-500">{{ $person->phone }}</span>
                                    @endif
                                    @if (! $person->email && ! $person->phone)
                                        <span class="text-slate-400">Not On File</span>
                                    @endif
                                </td>
                                <td class="text-slate-600">
                                    <span class="block truncate">{{ $person->address_line1 ?: '—' }}</span>
                                    @if ($person->city)
                                        <span class="block truncate text-slate-500">{{ $person->city }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                                              data-tip="Service Requests Filed">{{ $person->service_requests_count }} SR</span>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                                              data-tip="Form Submissions">{{ $person->form_submissions_count }} Forms</span>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                                              data-tip="Staff Logged Contacts">{{ $person->interactions_count }} Logged</span>
                                    </div>
                                </td>
                                <td class="text-slate-500">
                                    {{ $person->last_interaction_at?->diffForHumans() ?? 'Never' }}
                                </td>
                                <td class="text-right">
                                    <x-admin.row-actions :edit="route('constituents.edit', $person)"
                                                         :delete="route('constituents.destroy', $person)"
                                                         :name="'del-constituent-' . $person->id"
                                                         title="Delete This Resident Record?"
                                                         message="This removes the resident's record and their staff-logged contact history. Service requests and form submissions are kept, but will no longer be linked to a person." />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-admin.empty title="No Residents Match" icon="users"
                               message="Records are created automatically when someone files a service request or a form. You can also add one by hand."
                               :href="route('constituents.create')" label="Add A Resident" />
            @endif
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
