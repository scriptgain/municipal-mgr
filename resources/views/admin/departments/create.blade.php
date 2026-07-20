<x-admin.form-shell
    title="Add Department"
    subtitle="Create a new department for the public site."
    icon="building"
    :action="route('departments.store')"
    :index="route('departments.index')"
    :record="$record" :multipart="true">
    @include('admin.departments._form')
</x-admin.form-shell>
