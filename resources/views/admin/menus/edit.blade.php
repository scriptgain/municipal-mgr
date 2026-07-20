<x-admin.form-shell
    :title="'Edit ' . ($record->title ?? $record->name ?? 'Menu Item')"
    subtitle="Changes go live on the public site as soon as you save."
    icon="globe"
    :action="route('menus.update', $record)"
    method="PUT"
    :index="route('menus.index')"
    :record="$record"
    :deleteAction="route('menus.destroy', $record)">
    @include('admin.menus._form')
</x-admin.form-shell>
