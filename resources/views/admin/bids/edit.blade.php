<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Bid Or RFP')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="database"
    :action="route('bids.update', $record)"
    method="PUT"
    :index="route('bids.index')"
    :record="$record"
    :deleteAction="route('bids.destroy', $record)">
    @include('admin.bids._form')
</x-admin.form-shell>
