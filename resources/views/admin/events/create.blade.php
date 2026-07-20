<x-admin.form-shell
    title="Add Event"
    subtitle="Create a new event for the public site."
    icon="clock"
    :action="route('events.store')"
    :index="route('events.index')"
    :record="$record" :multipart="true">
    @include('admin.events._form')
</x-admin.form-shell>
