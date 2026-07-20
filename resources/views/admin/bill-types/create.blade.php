<x-admin.form-shell title="Add A Bill Type" icon="archive"
                    subtitle="Something residents can pay for."
                    :action="route('bill-types.store')"
                    :index="route('bill-types.index')"
                    :record="$record">
    @include('admin.bill-types._fields', ['record' => $record])
</x-admin.form-shell>
