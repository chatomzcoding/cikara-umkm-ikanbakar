<?php

use App\Models\ErrorPageSetting;
use App\Services\ErrorPageManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public int $statusCode = 403;
    public array $form = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', ErrorPageSetting::class);
        $this->loadForm();
    }

    protected function rules(): array
    {
        return [
            'form.is_active' => ['required', 'boolean'],
            'form.title' => ['required', 'string', 'max:120'],
            'form.message' => ['required', 'string', 'max:1000'],
            'form.accent' => ['required', Rule::in(array_keys(ErrorPageManager::ACCENTS))],
            'form.button_label' => ['nullable', 'string', 'max:50', 'required_with:form.button_url'],
            'form.button_url' => ['nullable', 'string', 'max:255', 'regex:/^\/(?!\/)/', 'required_with:form.button_label'],
            'form.show_back_button' => ['required', 'boolean'],
        ];
    }

    public function selectCode(int $statusCode): void
    {
        abort_unless(in_array($statusCode, ErrorPageManager::SUPPORTED_CODES, true), 404);
        $this->statusCode = $statusCode;
        $this->resetValidation();
        $this->loadForm();
    }

    public function toggleCustom(): void
    {
        $this->form['is_active'] = ! ($this->form['is_active'] ?? false);
    }

    public function toggleBackButton(): void
    {
        $this->form['show_back_button'] = ! ($this->form['show_back_button'] ?? false);
    }

    public function save(): void
    {
        $setting = ErrorPageSetting::query()->firstOrNew(['status_code' => $this->statusCode]);
        Gate::authorize('update', $setting);
        $setting->fill($this->validate()['form'])->save();
        app(ErrorPageManager::class)->forget($this->statusCode);
        $this->loadForm();
        $this->dispatch('notify', text: "Halaman error {$this->statusCode} berhasil diperbarui.", variant: 'success');
    }

    public function resetToDefault(): void
    {
        $setting = ErrorPageSetting::query()->firstOrNew(['status_code' => $this->statusCode]);
        Gate::authorize('update', $setting);
        $setting->fill(['is_active' => true, ...ErrorPageManager::defaults()[$this->statusCode]])->save();
        app(ErrorPageManager::class)->forget($this->statusCode);
        $this->resetValidation();
        $this->loadForm();
        $this->dispatch('notify', text: "Halaman error {$this->statusCode} dikembalikan ke pengaturan awal.", variant: 'success');
    }

    private function loadForm(): void
    {
        $default = ErrorPageManager::defaults()[$this->statusCode];
        $setting = ErrorPageSetting::query()->where('status_code', $this->statusCode)->first();
        $this->form = [
            'is_active' => $setting?->is_active ?? true,
            'title' => $setting?->title ?? $default['title'],
            'message' => $setting?->message ?? $default['message'],
            'accent' => $setting?->accent ?? $default['accent'],
            'button_label' => $setting?->button_label ?? $default['button_label'],
            'button_url' => $setting?->button_url ?? $default['button_url'],
            'show_back_button' => $setting?->show_back_button ?? $default['show_back_button'],
        ];
    }
};
?>

<div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
    <aside class="col-span-12 lg:col-span-3">
        <div class="card p-4 sm:p-5">
            <h2 class="font-medium text-slate-700 dark:text-navy-100">Kode Error</h2>
            <div class="mt-3 grid grid-cols-3 gap-2 lg:grid-cols-2">
                @foreach (App\Services\ErrorPageManager::SUPPORTED_CODES as $code)
                    <button type="button" wire:click="selectCode({{ $code }})"
                        class="btn h-10 border px-3 font-semibold {{ $statusCode === $code ? 'border-primary bg-primary text-white' : 'border-slate-300 bg-white text-slate-700 dark:border-navy-450 dark:bg-navy-700 dark:text-navy-100' }}">
                        {{ $code }}
                    </button>
                @endforeach
            </div>
        </div>
    </aside>

    <section class="col-span-12 lg:col-span-9">
        <div class="card">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
                <div><h2 class="font-medium text-slate-700 dark:text-navy-100">Pengaturan Error {{ $statusCode }}</h2><p class="mt-1 text-xs text-slate-500">Konten disimpan sebagai teks aman tanpa HTML.</p></div>
                <a href="{{ route('superadmin.sistem.error-page.preview', $statusCode) }}" target="_blank" class="btn h-8 gap-1.5 bg-info px-3 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg> Preview
                </a>
            </header>

            <form wire:submit="save" class="space-y-4 p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                    <div>
                        <p class="font-medium text-slate-700 dark:text-navy-100">Pengaturan custom</p>
                        <p class="mt-1 text-xs text-slate-500">Jika nonaktif, halaman memakai konten bawaan sistem.</p>
                    </div>
                    <button type="button" wire:click="toggleCustom" aria-pressed="{{ $form['is_active'] ? 'true' : 'false' }}"
                        class="btn h-9 min-w-28 gap-2 px-3 {{ $form['is_active'] ? 'bg-success text-white' : 'bg-slate-200 text-slate-700 dark:bg-navy-500 dark:text-navy-100' }}">
                        <span class="size-2.5 rounded-full bg-current"></span>
                        {{ $form['is_active'] ? 'Aktif' : 'Nonaktif' }}
                    </button>
                </div>
                <label class="block"><span>Judul <span class="text-error">*</span></span><input type="text" wire:model="form.title" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450">@error('form.title') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror</label>
                <label class="block"><span>Pesan <span class="text-error">*</span></span><textarea wire:model="form.message" rows="4" class="form-textarea mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450"></textarea>@error('form.message') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror</label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span>Warna aksen</span><select wire:model="form.accent" class="form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-navy-450 dark:bg-navy-700">@foreach (App\Services\ErrorPageManager::ACCENTS as $name => $color)<option value="{{ $name }}">{{ ucfirst($name) }}</option>@endforeach</select></label>
                    <label class="block"><span>Label tombol utama</span><input type="text" wire:model="form.button_label" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450">@error('form.button_label') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror</label>
                    <label class="block sm:col-span-2"><span>URL tombol utama</span><input type="text" wire:model="form.button_url" placeholder="/dashboard" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450"><span class="mt-1 block text-xs text-slate-400">Gunakan URL internal yang diawali satu garis miring.</span>@error('form.button_url') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror</label>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-navy-500">
                    <div>
                        <p class="font-medium text-slate-700 dark:text-navy-100">Tombol kembali</p>
                        <p class="mt-1 text-xs text-slate-500">Menampilkan action untuk kembali ke halaman sebelumnya.</p>
                    </div>
                    <button type="button" wire:click="toggleBackButton" aria-pressed="{{ $form['show_back_button'] ? 'true' : 'false' }}"
                        class="btn h-9 min-w-28 gap-2 px-3 {{ $form['show_back_button'] ? 'bg-success text-white' : 'bg-slate-200 text-slate-700 dark:bg-navy-500 dark:text-navy-100' }}">
                        <span class="size-2.5 rounded-full bg-current"></span>
                        {{ $form['show_back_button'] ? 'Tampil' : 'Disembunyikan' }}
                    </button>
                </div>

                @can('update', App\Models\ErrorPageSetting::query()->firstOrNew(['status_code' => $statusCode]))
                    <footer class="flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-4 dark:border-navy-500">
                        <button type="button" wire:click="resetToDefault" class="btn h-9 gap-1.5 bg-slate-200 px-4 text-slate-700 dark:bg-navy-500 dark:text-navy-100"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg> Reset</button>
                        <button type="submit" class="btn h-9 gap-1.5 bg-primary px-4 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg> Simpan</button>
                    </footer>
                @endcan
            </form>
        </div>
    </section>
</div>
