<x-layouts.app title="Add A Booking Record">
    <x-page-header title="Add A Booking Record" icon="shield"
                   subtitle="New records are saved unpublished. Publishing is a separate, deliberate step.">
        <x-slot:actions>
            <x-button variant="secondary" icon="chevron-left" :href="route('arrest-records.index')">Back To List</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('arrest-records.store') }}" class="space-y-6" enctype="multipart/form-data">
        @csrf

        @include('admin.arrest-records._form')

        <div class="section-divider pt-5 flex flex-wrap items-center justify-end gap-3">
            <x-button variant="secondary" :href="route('arrest-records.index')">Cancel</x-button>
            <x-button type="submit" icon="check">Save Record</x-button>
        </div>
    </form>
</x-layouts.app>
