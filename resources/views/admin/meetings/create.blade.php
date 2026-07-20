<x-admin.form-shell
    title="Add Meeting"
    subtitle="Create a new meeting for the public site."
    icon="clock"
    :action="route('meetings.store')"
    :index="route('meetings.index')"
    :record="$record">
    @include('admin.meetings._form')
</x-admin.form-shell>
