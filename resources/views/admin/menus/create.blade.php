<x-admin.form-shell
    title="Add Menu Item"
    subtitle="Create a new menu item for the public site."
    icon="globe"
    :action="route('menus.store')"
    :index="route('menus.index')"
    :record="$record">
    @include('admin.menus._form')
</x-admin.form-shell>
