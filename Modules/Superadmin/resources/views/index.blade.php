<x-lineone menu="master">
    @push('styles')
        <script src="{{ asset('js/pages/dashboards-crm-analytics.js') }}"></script>
    @endpush
    <livewire:superadmin.dashboard />
</x-lineone>