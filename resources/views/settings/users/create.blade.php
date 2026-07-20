<x-admin.form-shell
    title="Add User"
    subtitle="Create a staff login account."
    icon="users"
    :action="route('settings.users.store')"
    :index="route('settings.users.index')"
    :record="$user">
    @include('settings.users._form')
</x-admin.form-shell>
