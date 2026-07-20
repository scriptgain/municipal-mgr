<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Form')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="edit"
    :action="route('forms.update', $record)"
    method="PUT"
    :index="route('forms.index')"
    :record="$record"
    :deleteAction="route('forms.destroy', $record)">
    @include('admin.forms._form')
</x-admin.form-shell>
