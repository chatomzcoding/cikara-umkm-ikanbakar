@props([
    'deletePermission' => null,
    'restorePermission' => null,
    'forceDeletePermission' => null,
    'selected' => [],
    'trashMode' => 'active',
])

@if (count($selected) > 0)
    <div {{ $attributes->class(['flex flex-wrap items-center gap-2 rounded-lg bg-primary/10 px-3 py-2 text-sm']) }}>
        <span class="font-medium text-primary">{{ count($selected) }} data dipilih</span>

        @if ($trashMode === 'active')
            @if (! $deletePermission || auth()->user()?->can($deletePermission))
                    <button
                        type="button"
                        wire:click="$dispatch('swal-delete', {
                            name: 'crud-bulk-delete',
                            title: 'Pindahkan {{ count($selected) }} data ke sampah?',
                            text: 'Data terpilih dapat dipulihkan kembali dari menu Sampah.',
                            confirmButtonText: 'Ya, pindahkan!'
                        })"
                        class="btn h-8 gap-1.5 bg-error px-3 text-white"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18" /><path d="M8 6V4h8v2" /><path d="M19 6l-1 14H6L5 6" /><path d="M10 11v5M14 11v5" />
                        </svg>
                        Hapus
                    </button>
            @endif
        @endif

        @if ($trashMode === 'all')
            <span class="text-slate-500">Pilih filter Data aktif atau Sampah untuk menjalankan aksi massal.</span>
        @endif

        @if ($trashMode === 'trashed')
            @if (! $restorePermission || auth()->user()?->can($restorePermission))
                    <button type="button" wire:click="bulkRestore" class="btn h-8 gap-1.5 bg-success px-3 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 12a9 9 0 1 0 3-6.7L3 8" /><path d="M3 3v5h5" />
                        </svg>
                        Pulihkan
                    </button>
            @endif
            @if (! $forceDeletePermission || auth()->user()?->can($forceDeletePermission))
                    <button
                        type="button"
                        wire:click="$dispatch('swal-delete', {
                            name: 'crud-bulk-force-delete',
                            title: 'Hapus permanen {{ count($selected) }} data?',
                            text: 'Tindakan ini tidak dapat dibatalkan.',
                            confirmButtonText: 'Ya, hapus permanen!'
                        })"
                        class="btn h-8 gap-1.5 bg-error px-3 text-white"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18" /><path d="M8 6V4h8v2" /><path d="M19 6l-1 14H6L5 6" /><path d="m10 11 4 5M14 11l-4 5" />
                        </svg>
                        Hapus Permanen
                    </button>
            @endif
        @endif

        <button type="button" wire:click="clearSelection" class="btn h-8 gap-1.5 border border-slate-300 px-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" />
            </svg>
            Batal Pilih
        </button>
    </div>
@endif
