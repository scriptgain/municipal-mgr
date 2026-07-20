<x-admin.form-shell
    title="Add Elected Official"
    subtitle="Create a new elected official for the public site."
    icon="shield"
    :action="route('officials.store')"
    :index="route('officials.index')"
    :record="$record" :multipart="true">
    @include('admin.officials._form')
</x-admin.form-shell>
