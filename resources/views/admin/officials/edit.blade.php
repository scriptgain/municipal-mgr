<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Elected Official')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="shield"
    :action="route('officials.update', $record)"
    method="PUT"
    :index="route('officials.index')"
    :record="$record"
    :deleteAction="route('officials.destroy', $record)" :multipart="true">
    @include('admin.officials._form')
</x-admin.form-shell>
