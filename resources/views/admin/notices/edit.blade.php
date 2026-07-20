<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Public Notice')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="warning"
    :action="route('notices.update', $record)"
    method="PUT"
    :index="route('notices.index')"
    :record="$record"
    :deleteAction="route('notices.destroy', $record)">
    @include('admin.notices._form')
</x-admin.form-shell>
