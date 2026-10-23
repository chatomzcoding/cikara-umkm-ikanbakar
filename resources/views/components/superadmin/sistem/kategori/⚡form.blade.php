<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use Modules\Superadmin\Models\Sistemkategori;
use App\Services\StorageService;
use Illuminate\Support\Facades\Gate;


new class extends Component
{
    use WithFileUploads;

    public $id,$lock=true;

    public $form = [
        'nama' => null,
        'keterangan' => null,
        'is_status' => 1,
    ];

    public $gambar;

    public function mount(): void
    {
        Gate::authorize('viewAny', Sistemkategori::class);
    }

    #[On('sistem-kategori-edit')]
    function buttonEdit($id) {
        $data = Sistemkategori::findOrFail($id);
        Gate::authorize('update', $data);
        $this->id = $id;
        $this->form = [
            'nama' => $data->nama,
            'keterangan' => $data->keterangan,
            'is_status' => $data->is_status
        ];
    }

    function buttonBatalkan() {
        $this->reset(['form','id']);
    }

    function save(){
        // $service = New StorageService;
        // if ($this->gambar) {
        //     $path = $service->uploadS3Livewire('linqo/wedding/sistem',$this->gambar);
        //     $this->form['gambar'] = $path;
        // }
        if ($this->id) {
            $item = Sistemkategori::findOrFail($this->id);
            Gate::authorize('update', $item);
            $item->update($this->form);
            $info = 'diperbaharui';
        } else {
            Gate::authorize('create', Sistemkategori::class);
            Sistemkategori::create($this->form);
            $info = 'ditambahkan';
        }

        $this->reset(['form','id','gambar']);
        // $this->dispatch('filepond-clear'); // kirim event ke JS
        $this->resetValidation();

        $this->dispatch('notify',
            text: "Data Sistem Kategori berhasil $info"
        );

        $this->dispatch('handle-form');
    }
};
?>

<div>
    @include('layouts.button-form',['judul' => 'Sistem Kategori'])
    <form wire:submit='save'>
        <label class="block">
            <span>Nama:</span>
            <input
                class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                placeholder="Masukkan Nama"
                type="text"
                wire:model='form.nama'
                required
            />
            @error('form.nama')
                    <span class="text-tiny-plus text-error">{{ $message }}</span>
            @enderror
        </label>
        {{-- <div class="grid grid-cols-2 gap-3 mt-2">
            <label class="block">
                <span>Nominal Harga:</span>
                <input
                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                    placeholder="Masukkan Nominal"
                    type="text"
                    wire:model='form.nominal_harga'
                    required
                /> 
                @error('form.nominal_harga')
                        <span class="text-tiny-plus text-error">{{ $message }}</span>
                @enderror
            </label>
            <label class="block">
                <span>Nominal Promo:</span>
                <input
                    class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                    placeholder="Masukkan Nominal"
                    type="text"
                    wire:model='form.nominal_promo'
                    required
                /> 
                @error('form.nominal_promo')
                        <span class="text-tiny-plus text-error">{{ $message }}</span>
                @enderror
            </label>
        </div> --}}
        <label class="block mt-2">
            <span>Status:</span>
            <select
                class="form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:bg-navy-700 dark:hover:border-navy-400 dark:focus:border-accent"
                wire:model='form.is_status'
                >
                <option value="1">Aktif</option>
                <option value="0">Tidak Aktif</option>
            </select>
        </label>
        {{-- <label class="block mt-2">
            <span>Gambar:</span>
            @if ($id)
                <br> <small class="text-warning">Upload ulang jika ingin merubah gambar</small>
            @endif
            <div
                wire:ignore
                class="filepond fp-bordered"
                    x-data
                    x-init="
                        const pond = FilePond.create($refs.input);

                        pond.on('addfile', (error, file) => {
                        if (error) return;
                        @this.upload('gambar', file.file, () => {}, () => {});
                        });

                        pond.on('removefile', () => {
                        @this.set('gambar', null);
                        });
                        window.addEventListener('filepond-clear', () => {
                        pond.removeFiles(); // hapus semua item di UI
                        });
                                                "
                    >
                <input type="file" x-ref="input">
            </div>
        </label> --}}
        <label class="block">
            <span>Keterangan:</span>
            <textarea
                class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                placeholder="Masukkan Keterangan"
                wire:model='form.keterangan'
            ></textarea>
            @error('form.keterangan')
                    <span class="text-tiny-plus text-error">{{ $message }}</span>
            @enderror
        </label>
        @include('layouts.button-form-footer')
    </form>
</div>
