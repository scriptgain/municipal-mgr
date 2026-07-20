<x-admin.form-shell
    title="Add Page"
    subtitle="Create a new page for the public site."
    icon="book"
    :action="route('pages.store')"
    :index="route('pages.index')"
    :record="$record" :multipart="true">
    @include('admin.pages._form')
</x-admin.form-shell>
