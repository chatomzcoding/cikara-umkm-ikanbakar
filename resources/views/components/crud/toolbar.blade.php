@props([
    'exportPermission' => null,
    'showExport' => null,
    'excelPermission' => null,
    'showExcel' => null,
    'pdfPermission' => null,
    'showPdf' => null,
    'showTrash' => true,
    'showImport' => false,
    'importPermission' => null,
    'importErrors' => [],
])

@php($showExportButton = $showExport ?? filled($exportPermission))
@php($excelPermission = $excelPermission ?? $exportPermission)
@php($showExcelButton = $showExcel ?? filled($excelPermission))
@php($pdfPermission = $pdfPermission ?? $exportPermission)
@php($showPdfButton = $showPdf ?? filled($pdfPermission))

<div {{ $attributes->class(['flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="flex flex-1 flex-wrap items-center gap-2">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Cari data..."
            class="form-input h-9 min-w-48 rounded-lg border border-slate-300 bg-transparent px-3 dark:border-navy-450"
        >

        <select wire:model.live="perPage" class="form-select h-9 rounded-lg border border-slate-300 bg-transparent px-2 dark:border-navy-450">
            @foreach ([5, 10, 25, 50, 100] as $size)
                <option value="{{ $size }}">{{ $size }} Halaman</option>
            @endforeach
        </select>

        @if ($showTrash)
            <select wire:model.live="trashMode" class="form-select h-9 rounded-lg border border-slate-300 bg-transparent px-2 dark:border-navy-450">
                <option value="active">Data aktif</option>
                <option value="trashed">Sampah</option>
                <option value="all">Semua data</option>
            </select>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if ($showImport && (! $importPermission || auth()->user()?->can($importPermission)))
            <x-crud.import :errors="$importErrors" />
        @endif
        @if ($showExportButton)
            {{-- @if (! $exportPermission || auth()->user()?->can($exportPermission))
                <button type="button" wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv" class="btn h-9 gap-1.5 bg-success px-3 text-white disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3v12" />
                        <path d="m8 11 4 4 4-4" />
                        <path d="M5 21h14" />
                    </svg>
                    Export CSV
                </button>
            @endif --}}
        @endif
        @if ($showExcelButton)
            @if (! $excelPermission || auth()->user()?->can($excelPermission))
                <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="btn h-9 gap-1.5 bg-success px-3 text-white hover:bg-success-focus focus:bg-success-focus disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                        <path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z" />
                        <path d="m9 13 4 4M13 13l-4 4" />
                    </svg>
                    Export
                </button>
            @endif
        @endif
        @if ($showPdfButton)
            @if (! $pdfPermission || auth()->user()?->can($pdfPermission))
                <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf" class="btn h-9 gap-1.5 bg-error px-3 text-white disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 9V4h9l3 3v2" />
                        <path d="M6 18v3h12v-3" />
                        <rect x="3" y="9" width="18" height="9" rx="1" />
                    </svg>
                    Cetak
                </button>
            @endif
        @endif
        <button type="button" wire:click="resetCrudTable" class="btn h-9 gap-1.5 border border-slate-300 px-3 dark:border-navy-450">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5" />
                <path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5" />
            </svg>
            Reset
        </button>
    </div>
</div>
