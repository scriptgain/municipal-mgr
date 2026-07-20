<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Page')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="book"
    :action="route('pages.update', $record)"
    method="PUT"
    :index="route('pages.index')"
    :record="$record"
    :deleteAction="route('pages.destroy', $record)" :multipart="true">
    @include('admin.pages._form')
</x-admin.form-shell>
