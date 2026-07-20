<x-admin.form-shell
    title="Add Public Notice"
    subtitle="Create a new public notice for the public site."
    icon="warning"
    :action="route('notices.store')"
    :index="route('notices.index')"
    :record="$record">
    @include('admin.notices._form')
</x-admin.form-shell>
