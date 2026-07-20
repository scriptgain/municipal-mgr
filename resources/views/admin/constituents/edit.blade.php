<x-admin.form-shell :title="'Edit ' . $record->name" icon="users"
                    subtitle="Resident record. Staff only."
                    :action="route('constituents.update', $record)"
                    method="PUT"
                    :index="route('constituents.show', $record)"
                    :record="$record"
                    :deleteAction="route('constituents.destroy', $record)">
    @include('admin.constituents._fields')
</x-admin.form-shell>
