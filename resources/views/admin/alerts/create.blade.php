<x-admin.form-shell
    title="Add Alert"
    subtitle="Create a new alert for the public site."
    icon="bolt"
    :action="route('alerts.store')"
    :index="route('alerts.index')"
    :record="$record">
    @include('admin.alerts._form')
</x-admin.form-shell>
