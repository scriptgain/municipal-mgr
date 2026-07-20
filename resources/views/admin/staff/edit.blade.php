<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Staff Member')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="users"
    :action="route('staff.update', $record)"
    method="PUT"
    :index="route('staff.index')"
    :record="$record"
    :deleteAction="route('staff.destroy', $record)" :multipart="true">
    @include('admin.staff._form')
</x-admin.form-shell>
