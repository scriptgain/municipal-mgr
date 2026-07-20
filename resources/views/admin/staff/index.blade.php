<x-admin.index-shell title="Staff Directory" icon="users"
    subtitle="Public directory entries. These are listings, not login accounts."
    :records="$records" :search="$search" placeholder="Search Staff…"
    :createHref="route('staff.create')" createLabel="Add Staff Member"
    :bulkAction="route('staff.bulk-destroy')" label="Staff Member"
    emptyTitle="No Staff Listed"
    emptyMessage="A published directory is one of the most-used pages on a municipal site.">
    <x-slot:actions>
        <x-button variant="secondary" icon="users" :href="route('settings.users.index')">Login Accounts</x-button>
    </x-slot:actions>

    <x-table flush>
        <thead>
            <tr>
                <th class="w-10"><x-select-all /></th>
                <th>Name</th>
                <th>Department</th>
                <th>Contact</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $member)
                <tr>
                    <td><x-select-row :id="$member->id" :label="$member->name" /></td>
                    <td>
                        <div class="flex items-center gap-3 min-w-0">
                            <x-avatar size="sm" :initials="$member->initials()" :name="$member->name"
                                      :src="$member->photo_path ? municipal_upload_url($member->photo_path) : null" />
                            <span class="min-w-0">
                                <span class="block font-medium text-slate-900 truncate">{{ $member->name }}</span>
                                <span class="block text-xs text-slate-400 truncate">{{ $member->job_title }}</span>
                            </span>
                        </div>
                    </td>
                    <td>{{ $member->department?->name ?? '–' }}</td>
                    <td class="text-slate-600">{{ $member->phoneDisplay() ?: ($member->email ?: '–') }}</td>
                    <td>
                        @if ($member->is_published)
                            <x-badge color="success" dot>Listed</x-badge>
                        @else
                            <x-badge color="neutral" dot>Hidden</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <x-admin.row-actions :name="'del-staff-' . $member->id"
                            :edit="route('staff.edit', $member)"
                            :delete="route('staff.destroy', $member)"
                            title="Remove This Staff Member?" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-admin.index-shell>
