<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Data\Models\Siswa;

new class extends Component
{
    public $id;

    public array $form = [
        'nama' => null,
        'nis' => null,
        'alamat' => null,
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', Siswa::class);
    }

    protected function rules(): array
    {
        return [
            'form.nama' => ['required', 'string', 'max:255'],
            'form.nis' => ['required', 'string', 'max:255'],
            'form.alamat' => ['nullable', 'string'],
        ];
    }

    #[On('data.siswas-edit')]
    public function edit(int|string $id): void
    {
        $data = Siswa::findOrFail($id);
        Gate::authorize('update', $data);
        $this->id = $data->getKey();
        $this->form = [
            'nama' => $data->nama,
            'nis' => $data->nis,
            'alamat' => $data->alamat,
        ];
    }

    public function buttonReset(): void
    {
        $this->resetForm();
    }

    public function buttonBatalkan(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $data = $this->validate()['form'];
        $model = $this->id ? Siswa::findOrFail($this->id) : new Siswa;
        Gate::authorize($model->exists ? 'update' : 'create', $model->exists ? $model : Siswa::class);
        $model->fill($data)->save();
        $message = $this->id ? 'diperbarui' : 'ditambahkan';
        $this->resetForm();
        $this->dispatch('notify', text: 'Data Siswa berhasil '.$message);
        $this->dispatch('handle-form');
    }

    private function resetForm(): void
    {
        $this->reset(['id', 'form']);
        $this->resetValidation();
    }
};
?>

<div>
    @include('layouts.button-form', ['judul' => 'Siswa'])
    <form wire:submit="save" class="space-y-4">
        <label class="block"><span>Nama</span><input type="text" wire:model="form.nama" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2">@error('form.nama') <span class="text-xs text-error">{{ $message }}</span> @enderror</label>
        <label class="block"><span>Nis</span><input type="text" wire:model="form.nis" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2">@error('form.nis') <span class="text-xs text-error">{{ $message }}</span> @enderror</label>
        <label class="block"><span>Alamat</span><textarea wire:model="form.alamat" class="form-textarea mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2"></textarea>@error('form.alamat') <span class="text-xs text-error">{{ $message }}</span> @enderror</label>
        <div class="border-t border-slate-200 pt-4 text-right dark:border-navy-500">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="btn gap-2 bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus disabled:cursor-not-allowed disabled:opacity-60 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                Simpan
            </button>
            <button
                type="button"
                wire:click="buttonBatalkan"
                @click="openForm = false"
                class="btn gap-2 bg-error font-medium text-white hover:bg-error-focus focus:bg-error-focus"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
                Batalkan
            </button>
        </div>
    </form>
</div>
