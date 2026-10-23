@php
    $faviconPath = \Illuminate\Support\Facades\Schema::hasTable('data_pokok')
        ? \Modules\Superadmin\Models\DataPokok::query()->value('favicon')
        : null;

    $faviconUrl = $faviconPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconPath)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($faviconPath)
        : asset('favicon.ico');

    $faviconType = match (strtolower(pathinfo($faviconPath ?? 'favicon.ico', PATHINFO_EXTENSION))) {
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        default => 'image/x-icon',
    };
@endphp

<link id="app-favicon" rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('favicon-updated', (event) => {
            const favicon = document.getElementById('app-favicon');

            if (favicon) {
                favicon.href = event.url;
                favicon.type = event.type;
            }
        });
    });
</script>
