<x-admin.form-shell :title="'Edit ' . $record->label" icon="archive"
                    subtitle="Changing this affects how residents pay it from now on."
                    :action="route('bill-types.update', $record)"
                    method="PUT"
                    :index="route('bill-types.index')"
                    :record="$record"
                    :deleteAction="route('bill-types.destroy', $record)">
    @include('admin.bill-types._fields', ['record' => $record])
</x-admin.form-shell>
