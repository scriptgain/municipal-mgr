<x-admin.form-shell
    title="Add Release Note"
    subtitle="Publish a dated entry to the public What's New page."
    icon="megaphone"
    :action="route('changelog.store')"
    :index="route('changelog.index')"
    :record="$record">
    @include('admin.changelog._form')
</x-admin.form-shell>
