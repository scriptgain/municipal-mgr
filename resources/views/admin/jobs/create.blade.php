<x-admin.form-shell
    title="Add Job Posting"
    subtitle="Create a new job posting for the public site."
    icon="users"
    :action="route('jobs.store')"
    :index="route('jobs.index')"
    :record="$record" :multipart="true">
    @include('admin.jobs._form')
</x-admin.form-shell>
