<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Document')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="archive"
    :action="route('documents.update', $record)"
    method="PUT"
    :index="route('documents.index')"
    :record="$record"
    :deleteAction="route('documents.destroy', $record)" :multipart="true">
    @include('admin.documents._form')
</x-admin.form-shell>
