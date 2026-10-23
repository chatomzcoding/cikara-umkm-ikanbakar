<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use Modules\Superadmin\Models\Sistemkategori;
use Modules\Superadmin\Models\Sistemkategorisub;
use Illuminate\Support\Facades\Gate;

new class extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $id,$leftComponent;

    public $search, $pagination = 5, $sort=true,$judul='Sistem Kategori';

    public $uuid;

    public $filter = [
        'label' => 'semua',
    ];

    function mount($uuid) {
        $this->uuid = $uuid;
        Gate::authorize('view', Sistemkategori::where('uuid', $uuid)->firstOrFail());
    }

    #[Computed]
    public function parameters($format='pdf'){
        $parameter = [
            's' => 'sistem-kategori',
            'format' => $format
        ];
        $parameter = array_merge($parameter,$this->filter);
        return $parameter;
    }

    #[Computed]
    public function sistemkategori(){
        return Sistemkategori::where('uuid',$this->uuid)->first();
    }

    #[Computed]
    public function items(){
        // $label = $this->filter['label'];
        return Sistemkategorisub::where('sistemkategori_id',$this->sistemkategori->id)
                        ->search($this->search)
                        // ->when($label !== 'semua', fn($q) => 
                        //     $q->where('label',$label))
                        ->sorting($this->sort,'nama')
                        ->paginate($this->pagination);
    }

    function buttonEdit() {
        Gate::authorize('update', $this->sistemkategori);
        $id = $this->sistemkategori->id;
        $this->leftComponent = 'superadmin.sistem.kategori.form';
        $this->dispatch('sistem-kategori-edit',$id);
    }

    function buttonTambahSub() {
        Gate::authorize('create', Sistemkategorisub::class);
        $id = $this->sistemkategori->id;
        $this->leftComponent = 'superadmin.sistem.kategori.sub.form';
        $this->dispatch('sistem-kategori-sub-tambah',$id);
    }

    function buttonEditSub($id) {
        Gate::authorize('update', Sistemkategorisub::findOrFail($id));
        $this->leftComponent = 'superadmin.sistem.kategori.sub.form';
        $this->dispatch('sistem-kategori-sub-edit',$id);
    }

    function buttonResetFilter() {
        $this->reset(['filter','pagination','sort','search']);
    }

    #[On('delete-item')]
    function delete($id) {
        $item = Sistemkategorisub::findOrFail($id);
        Gate::authorize('delete', $item);
        $item->delete();
        $this->dispatch('notify',
            text: "Data $this->judul berhasil dihapus",
            variant: 'deleted'
        );
    }

    #[On('handle-form')]
    function handleForm() {
        $this->resetPage();
    }

    function updated() {
        $this->resetPage();
    }
};
?>

<div
    x-data="{ 
        openForm: false,
    }"
    >
    <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
        <div class="col-span-12 lg:col-span-4" x-show="openForm">
           @include('layouts.form')
        </div>
        <div 
            class="col-span-12"
            :class="openForm ? 'lg:col-span-8' : 'lg:col-span-12'"
            >
            <div class="card">
                <div
                    class="flex flex-col space-y-4 border-b border-slate-200 p-4 dark:border-navy-500 sm:flex-row sm:justify-between sm:space-y-0 sm:px-5">
                    <div class="flex space-x-2">
                        <a
                            href="{{ route('superadmin.sistem.kategori.index') }}"
                            class="btn h-8 bg-secondary font-medium text-white hover:bg-secondary-focus focus:bg-secondary-focus active:bg-secondary-focus/90 gap-2"
                            >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-back-up size-5">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M9 14l-4 -4l4 -4" />
                            <path d="M5 10h11a4 4 0 1 1 0 8h-1" />
                        </svg><span class="hidden sm:block"> Kembali</span>
                        </a>
                        @can('create', Modules\Superadmin\Models\Sistemkategorisub::class)
                        <button
                            type="button"
                            class="btn h-8 bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90"
                            @click="openForm = true"
                            wire:click="buttonTambahSub"
                            >
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg> <span class="hidden sm:block">Tambah</span>
                        </button>
                        @endcan
                        @can('update', $this->sistemkategori)
                        <button
                            type="button"
                            class="btn h-8 bg-success font-medium text-white hover:bg-success-focus focus:bg-success-focus active:bg-success-focus/90"
                            @click="openForm = true"
                            wire:click="buttonEdit"
                            >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg> <span class="hidden sm:block">Edit</span>
                        </button>
                        @endcan
                    </div>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="flex items-center justify-end sm:justify-between">
                        <h2 class="hidden sm:block text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                            Daftar {{ $judul }}
                        </h2>
                        <div class="flex">
                            @include('layouts.button-list')
                            <div class="inline-flex" data-popper wire:ignore.self>
                                <button
                                     wire:ignore
                                    class="popper-ref btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                                <div class="popper-root" wire:ignore.self> 
                                    <div
                                        class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                                        {{-- <ul>
                                            <li>
                                                <a 
                                                    href="{{ route('cetak.index',$this->parameters) }}"
                                                    target="_blank"
                                                    class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><path d="M7 15a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2l0 -4" /></svg> Cetak
                                                </a>
                                            </li>
                                            <li>
                                                <a 
                                                    href="{{ route('cetak.index',$this->parameters('excel')) }}"
                                                    class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.5 21h-7.5a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v7.5" /><path d="M3 10h18" /><path d="M10 3v18" /><path d="M16 19h6" /><path d="M19 16l3 3l-3 3" /></svg> Export
                                                </a>
                                            </li>
                                        </ul> --}}
                                        <div class="my-1 h-px bg-slate-150 dark:bg-navy-500"></div>
                                        {{-- <ul>
                                            <li>
                                                <a 
                                                    href="#"
                                                    wire:click="buttonGenerateUuid"
                                                    class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.5 21h-5.5a2 2 0 0 1 -2 -2v-6a2 2 0 0 1 2 -2h10a2 2 0 0 1 1.74 1.012" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /><path d="M16 19h6" /><path d="M19 16v6" /></svg> Generate UUID
                                                </a>
                                            </li>
                                        </ul> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <label for="" class="block">
                                <select
                                    class="form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:bg-navy-700 dark:hover:border-navy-400 dark:focus:border-accent"
                                    wire:model.live='filter.label'
                                    >
                                    <option value="semua">-- semua --</option>
                                        @foreach ($this->label as $item)
                                        <option value="{{ $item->nama }}">{{ $item->nama }}</option>
                                    @endforeach
                                </select>
                                <span class="italic text-xs">Label</span>
                            </label>
                        </div>
                    </div> --}}
                    <div class="is-scrollbar-hidden min-w-full overflow-x-auto mt-3">
                        <table class="is-hoverable w-full text-left">
                            <thead class="text-center">
                                <tr>
                                    <th
                                        class="whitespace-nowrap rounded-l-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                                    >
                                        Nama
                                    </th>
                                    <th
                                        class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                                    >
                                        Keterangan
                                    </th>
                                    <th
                                        class="whitespace-nowrap bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="whitespace-nowrap rounded-r-lg bg-slate-200 px-3 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                                        >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->items as $item)
                                    <tr 
                                        wire:key='item-{{ $item->id }}'
                                        class="border border-transparent border-b-slate-200 dark:border-b-navy-500">
                                        <td class="whitespace-nowrap rounded-l-lg px-4 py-3 sm:px-5">
                                             {{-- <div class="avatar size-24">
                                                <img
                                                class="mask rounded-lg"
                                                src="{{ $item->getGambar() }}"
                                                alt="avatar"
                                                />
                                            </div> --}}
                                            {{ $item->nama }} <br>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5">{{ $item->keterangan }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5 text-center">{{ $item->getStatus() }}</td>
                                        <td class="whitespace-nowrap rounded-r-lg px-4 py-3 sm:px-5 text-center">
                                            @can('update', $item)
                                            <button
                                                class="btn size-9 p-0 font-medium text-success hover:bg-error/20 focus:bg-success/20 active:bg-success/25"
                                                wire:click='buttonEditSub({{ $item->id }})'
                                                @click="openForm = true"
                                                >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg>
                                            </button>
                                            @endcan
                                            @can('delete', $item)
                                            <button
                                                class="btn size-9 p-0 font-medium text-error hover:bg-error/20 focus:bg-error/20 active:bg-error/25"
                                                wire:click="$dispatch('swal-delete',{ 'id' : {{ $item->id }}})"
                                                >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="size-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
                                                </svg>
                                            </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="text-center">
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5" colspan="8">Belum ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $this->items->links() }}
                    </div>
                    <div class="text-right">
                        <i class="text-xs">Layout - 04/02/26</i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
