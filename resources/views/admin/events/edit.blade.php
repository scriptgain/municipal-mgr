<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Event')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="clock"
    :action="route('events.update', $record)"
    method="PUT"
    :index="route('events.index')"
    :record="$record"
    :deleteAction="route('events.destroy', $record)" :multipart="true">
    @include('admin.events._form')
</x-admin.form-shell>
