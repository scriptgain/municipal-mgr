<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Document Category')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="folder"
    :action="route('document-categories.update', $record)"
    method="PUT"
    :index="route('document-categories.index')"
    :record="$record"
    :deleteAction="route('document-categories.destroy', $record)">
    @include('admin.document-categories._form')
</x-admin.form-shell>
