<x-admin.form-shell
    title="Add Staff Member"
    subtitle="Create a new staff member for the public site."
    icon="users"
    :action="route('staff.store')"
    :index="route('staff.index')"
    :record="$record" :multipart="true">
    @include('admin.staff._form')
</x-admin.form-shell>
