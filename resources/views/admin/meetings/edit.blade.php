<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Meeting')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="clock"
    :action="route('meetings.update', $record)"
    method="PUT"
    :index="route('meetings.index')"
    :record="$record"
    :deleteAction="route('meetings.destroy', $record)">
    @include('admin.meetings._form')
</x-admin.form-shell>
