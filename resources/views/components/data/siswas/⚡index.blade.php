<?php

use App\Livewire\Concerns\WithCrudImport;
use App\Livewire\Concerns\WithCrudTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Data\Models\Siswa;

new class extends Component
{
    use WithCrudImport;
    use WithCrudTable;

    public $id;
    public $leftComponent;

    public function mount(): void
    {
        Gate::authorize('viewAny', Siswa::class);
        $this->sortField = $this->crudDefaultSortField();
    }

    protected function crudModelClass(): string
    {
        return Siswa::class;
    }

    protected function crudSortableFields(): array
    {
        return ['nama', 'nis', 'alamat'];
    }

    protected function crudDefaultSortField(): string
    {
        return 'nama';
    }

    protected function crudExportPermission(): string
    {
        return 'data.siswas.export';
    }

    protected function crudUsesSoftDeletes(): bool
    {
        return true;
    }

    protected function crudImportPermission(): string
    {
        return 'data.siswas.import';
    }

    protected function crudImportRules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
        ];
    }

    protected function crudExportColumns(): array
    {
        return [
            'Nama' => 'nama',
            'Nis' => 'nis',
            'Alamat' => 'alamat',
        ];
    }

    protected function crudPdfTitle(): string
    {
        return 'Daftar Siswas';
    }

    // Pengaturan cetak PDF lain (hapus komentar bila perlu):
    // protected function crudPdfSubtitle(): ?string { return 'Tahun Ajaran 2026/2027'; }
    // protected function crudPdfOrientation(): string { return 'landscape'; }
    // protected function crudPdfPaper(): string { return 'f4'; }
    // protected function crudPdfView(): string { return 'exports.siswas'; }
    // protected function crudPdfColumns(): array { return ['Nama' => 'nama']; }

    protected function crudFilteredQuery(): Builder
    {
        $query = Siswa::query()->search($this->search);

        return $this->applySorting($this->applyTrashMode($query));
    }

    #[Computed]
    public function items()
    {
        return $this->crudFilteredQuery()->paginate($this->perPage);
    }

    public function buttonEdit(int|string $id): void
    {
        Gate::authorize('update', Siswa::findOrFail($id));
        $this->leftComponent = 'data.siswas.form';
        $this->dispatch('data.siswas-edit', $id);
    }

    #[On('handle-form')]
    public function handleForm(): void
    {
        $this->resetPage();
    }
};
?>

<div x-data="{ openForm: false }" x-on:handle-form.window="openForm = false">
    <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
        <div class="col-span-12 lg:col-span-4" x-show="openForm" x-cloak>@include('layouts.form')</div>
        <div class="col-span-12" :class="openForm ? 'lg:col-span-8' : 'lg:col-span-12'">
            <div class="card">
                <div class="flex items-center justify-between border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                    <h2 class="font-medium">Daftar Siswas</h2>
                    @can('create', Modules\Data\Models\Siswa::class)
                        <button type="button" class="btn h-8 gap-1.5 bg-primary px-3 text-white" @click="openForm = true" wire:click="$set('leftComponent', 'data.siswas.form')"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>Tambah</button>
                    @endcan
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    <x-crud.toolbar :show-export="true" :show-excel="true" :show-pdf="true" export-permission="data.siswas.export" :show-import="true" import-permission="data.siswas.import" :import-errors="$importErrors" :show-trash="true" />
                    <x-crud.bulk-actions :selected="$selected" :trash-mode="$trashMode" delete-permission="data.siswas.delete" restore-permission="data.siswas.restore" force-delete-permission="data.siswas.force-delete" />
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead><tr>
                                <th class="w-10 bg-slate-200 px-3 py-3 text-center dark:bg-navy-800">@if ($trashMode !== 'all') <x-crud.select-page :ids="$this->items->pluck('id')" :selected="$selected" /> @endif</th>
                                    <x-crud.sortable-header field="nama" label="Nama" :sort-field="$sortField" :sort-direction="$sortDirection" />
                                    <x-crud.sortable-header field="nis" label="Nis" :sort-field="$sortField" :sort-direction="$sortDirection" />
                                    <x-crud.sortable-header field="alamat" label="Alamat" :sort-field="$sortField" :sort-direction="$sortDirection" />
<th class="bg-slate-200 px-4 py-3 text-center font-semibold uppercase dark:bg-navy-800">Status</th>
                                <th class="bg-slate-200 px-4 py-3 text-center font-semibold uppercase dark:bg-navy-800">Aksi</th>
                            </tr></thead>
                            <tbody>
                                @forelse ($this->items as $item)
                                    <tr wire:key="siswas-{{ $item->id }}" class="border-b border-slate-200 dark:border-navy-500">
                                        <td class="px-3 py-3 text-center">@if ($trashMode !== 'all') <x-crud.row-checkbox :id="$item->id" /> @endif</td>
                                        <td class="px-4 py-3">{{ $item->nama }}</td>
                                        <td class="px-4 py-3">{{ $item->nis }}</td>
                                        <td class="px-4 py-3">{{ $item->alamat }}</td>
<td class="px-4 py-3 text-center"><span class="badge {{ $item->trashed() ? 'bg-error/10 text-error' : 'bg-success/10 text-success' }}">{{ $item->trashed() ? 'Sampah' : 'Aktif' }}</span></td>
                                        <td class="px-4 py-3 text-center">
                                            @if ($item->trashed())
                                                @can('restore', $item)
                                                    <button type="button" wire:click="restoreItem({{ $item->id }})" class="btn h-8 gap-1.5 bg-success px-3 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg>Pulihkan</button>
                                                @endcan
                                                @can('forceDelete', $item)
                                                    <button type="button" wire:click="$dispatch('swal-delete', { id: {{ $item->id }}, name: 'crud-force-delete-item', title: 'Hapus permanen?', text: 'Tindakan ini tidak dapat dibatalkan.' })" class="btn h-8 gap-1.5 bg-error px-3 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11l4 5M14 11l-4 5"/></svg>Hapus Permanen</button>
                                                @endcan
                                            @else
                                                @can('update', $item)
                                                    <button type="button" @click="openForm = true" wire:click="buttonEdit({{ $item->id }})" class="btn h-8 gap-1.5 bg-primary px-3 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>Edit</button>
                                                @endcan
                                                @can('delete', $item)
                                                    <button type="button" wire:click="$dispatch('swal-delete', { id: {{ $item->id }}, name: 'crud-delete-item', title: 'Hapus data?', text: 'Lanjutkan penghapusan data ini?' })" class="btn h-8 gap-1.5 bg-error px-3 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/></svg>Hapus</button>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <x-crud.empty-state colspan="6" message="Data tidak ditemukan." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $this->items->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
