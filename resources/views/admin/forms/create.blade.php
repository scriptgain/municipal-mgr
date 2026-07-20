<x-admin.form-shell
    title="Add Form"
    subtitle="Create a new form for the public site."
    icon="edit"
    :action="route('forms.store')"
    :index="route('forms.index')"
    :record="$record">
    @include('admin.forms._form')
</x-admin.form-shell>
