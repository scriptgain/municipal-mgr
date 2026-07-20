<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Job Posting')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="users"
    :action="route('jobs.update', $record)"
    method="PUT"
    :index="route('jobs.index')"
    :record="$record"
    :deleteAction="route('jobs.destroy', $record)" :multipart="true">
    @include('admin.jobs._form')
</x-admin.form-shell>
