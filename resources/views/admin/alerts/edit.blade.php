<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Alert')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="bolt"
    :action="route('alerts.update', $record)"
    method="PUT"
    :index="route('alerts.index')"
    :record="$record"
    :deleteAction="route('alerts.destroy', $record)">
    @include('admin.alerts._form')
</x-admin.form-shell>
