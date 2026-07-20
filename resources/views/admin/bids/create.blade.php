<x-admin.form-shell
    title="Add Bid Or RFP"
    subtitle="Create a new bid or rfp for the public site."
    icon="database"
    :action="route('bids.store')"
    :index="route('bids.index')"
    :record="$record" :multipart="true">
    @include('admin.bids._form')
</x-admin.form-shell>
