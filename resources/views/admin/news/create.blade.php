<x-admin.form-shell
    title="Add News Post"
    subtitle="Create a new news post for the public site."
    icon="bell"
    :action="route('news.store')"
    :index="route('news.index')"
    :record="$record" :multipart="true">
    @include('admin.news._form')
</x-admin.form-shell>
