<x-layouts.app title="Users And Roles">
    <x-page-header title="Users And Roles" icon="users"
                   subtitle="Staff login accounts. Department editors only see their own department's content.">
        <x-slot:actions>
            <x-button href="{{ route('settings.users.create') }}" icon="plus">Add User</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        <x-table flush>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3 min-w-0">
                                <x-avatar size="sm" :initials="$u->initials()" :name="$u->name" />
                                <span class="min-w-0">
                                    <span class="block font-medium text-slate-900 truncate">{{ $u->name }}</span>
                                    <span class="block text-xs text-slate-400 truncate">{{ $u->email }}</span>
                                </span>
                            </div>
                        </td>
                        <td><x-badge :color="$u->isAdmin() ? 'success' : 'info'">{{ $u->roleLabel() }}</x-badge></td>
                        <td>{{ $u->department?->name ?? '—' }}</td>
                        <td>
                            @if ($u->is_active)
                                <x-status-dot color="success" label="Active" />
                            @else
                                <x-status-dot color="neutral" label="Disabled" />
                            @endif
                        </td>
                        <td class="text-right">
                            <x-admin.row-actions :name="'del-user-' . $u->id"
                                :edit="route('settings.users.edit', $u)"
                                :delete="$u->id === auth()->id() ? null : route('settings.users.destroy', $u)"
                                title="Delete This User?"
                                message="The account is removed immediately. Content they authored is kept." />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
</x-layouts.app>
