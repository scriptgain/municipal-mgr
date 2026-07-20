<x-admin.form-shell
    :title="'Edit ' . $user->name"
    subtitle="Change this account's role, department, or password."
    icon="users"
    :action="route('settings.users.update', $user)"
    method="PUT"
    :index="route('settings.users.index')"
    :record="$user"
    :deleteAction="$user->id === auth()->id() ? null : route('settings.users.destroy', $user)">
    @include('settings.users._form')
</x-admin.form-shell>
