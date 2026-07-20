<x-admin.form-shell
    title="Add Document"
    subtitle="Create a new document for the public site."
    icon="archive"
    :action="route('documents.store')"
    :index="route('documents.index')"
    :record="$record" :multipart="true">
    @include('admin.documents._form')
</x-admin.form-shell>
