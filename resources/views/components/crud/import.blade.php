@props(['errors' => []])

<div
    x-data="{ open: @js($errors !== []), uploading: false, progress: 0 }"
    x-on:crud-import-finished.window="open = false"
    x-on:keydown.escape.window="open = false"
    class="contents"
>
    <button type="button" @click="open = true" class="btn h-9 gap-1.5 bg-info px-3 text-white hover:bg-info-focus focus:bg-info-focus">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 21V9" />
            <path d="m8 13 4-4 4 4" />
            <path d="M5 3h14" />
        </svg>
        Import
    </button>

    @teleport('body')
        <div x-show="open" x-cloak class="fixed inset-0 z-100 flex items-center justify-center overflow-y-auto px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="crud-import-title">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/60" @click="open = false; $wire.clearImport()"></div>

            <div x-show="open" x-transition class="relative w-full max-w-lg rounded-lg bg-white p-5 dark:bg-navy-700">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 id="crud-import-title" class="text-base font-medium text-slate-700 dark:text-navy-100">Import Data</h3>
                        <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">
                            Gunakan template agar judul kolom sesuai. Jika ada satu baris yang salah, tidak ada data yang disimpan.
                        </p>
                    </div>
                    <button type="button" @click="open = false; $wire.clearImport()" class="btn size-7 rounded-full p-0 hover:bg-slate-300/20" aria-label="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <button type="button" wire:click="downloadImportTemplate" class="btn h-9 w-full gap-1.5 border border-success px-3 text-success hover:bg-success/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3v12" /><path d="m8 11 4 4 4-4" /><path d="M5 21h14" />
                        </svg>
                        Unduh Template Excel
                    </button>

                    <label
                        class="block"
                        x-on:livewire-upload-start="uploading = true; progress = 0"
                        x-on:livewire-upload-finish="uploading = false; progress = 100"
                        x-on:livewire-upload-error="uploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress"
                    >
                        <span class="text-sm font-medium text-slate-700 dark:text-navy-100">File (.xlsx atau .csv, maks. 5 MB)</span>
                        <input
                            type="file"
                            wire:model="importFile"
                            accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
                            class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 text-xs dark:border-navy-450"
                        >
                        <div x-show="uploading" x-cloak class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-navy-500">
                            <div class="h-full rounded-full bg-info transition-[width]" :style="`width: ${progress}%`"></div>
                        </div>
                    </label>

                    @if ($errors !== [])
                        <div class="max-h-48 overflow-y-auto rounded-lg border border-error/30 bg-error/5 p-3 text-xs text-error">
                            <p class="mb-1 font-medium">Import dibatalkan:</p>
                            <ul class="list-inside list-disc space-y-0.5">
                                @foreach ($errors as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="open = false; $wire.clearImport()" class="btn h-9 bg-slate-150 px-4 text-slate-700 hover:bg-slate-200 dark:bg-navy-500 dark:text-navy-100">
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="importExcel"
                            wire:loading.attr="disabled"
                            wire:target="importExcel,importFile"
                            :disabled="uploading"
                            class="btn h-9 bg-primary px-4 text-white hover:bg-primary-focus disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="importExcel">Import</span>
                            <span wire:loading wire:target="importExcel">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endteleport
</div>
