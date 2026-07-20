<x-admin.form-shell title="Add A Resident" icon="users"
                    subtitle="Records are created automatically from online filings. Add one by hand for a walk-in or a phone call."
                    :action="route('constituents.store')"
                    :index="route('constituents.index')"
                    :record="$record">
    @include('admin.constituents._fields')
</x-admin.form-shell>
