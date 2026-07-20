<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'News Post')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="bell"
    :action="route('news.update', $record)"
    method="PUT"
    :index="route('news.index')"
    :record="$record"
    :deleteAction="route('news.destroy', $record)" :multipart="true">
    @include('admin.news._form')
</x-admin.form-shell>
