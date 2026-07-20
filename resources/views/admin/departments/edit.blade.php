<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Department')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="building"
    :action="route('departments.update', $record)"
    method="PUT"
    :index="route('departments.index')"
    :record="$record"
    :deleteAction="route('departments.destroy', $record)">
    @include('admin.departments._form')
</x-admin.form-shell>
