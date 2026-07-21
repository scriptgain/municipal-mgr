<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? 'Release Note')"
    subtitle="Changes go live on the What's New page as soon as you save."
    icon="megaphone"
    :action="route('changelog.update', $record)"
    method="PUT"
    :index="route('changelog.index')"
    :record="$record"
    :deleteAction="route('changelog.destroy', $record)">
    @include('admin.changelog._form')
</x-admin.form-shell>
