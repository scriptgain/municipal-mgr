<x-admin.form-shell
    title="Add Document Category"
    subtitle="Create a new document category for the public site."
    icon="folder"
    :action="route('document-categories.store')"
    :index="route('document-categories.index')"
    :record="$record">
    @include('admin.document-categories._form')
</x-admin.form-shell>
