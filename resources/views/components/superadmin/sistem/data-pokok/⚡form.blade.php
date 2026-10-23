<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Superadmin\Models\DataPokok;

new class extends Component
{
    use WithFileUploads;

    public ?int $id = null;

    public array $form = [];

    public $logo = null;

    public $favicon = null;

    public ?string $logoLama = null;

    public ?string $faviconLama = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', DataPokok::class);
        $this->id = DataPokok::query()->value('id');
        $this->loadForm();
    }

    protected function rules(): array
    {
        return [
            'form.nama_aplikasi' => ['required', 'string', 'max:255'],
            'form.keterangan' => ['nullable', 'string', 'max:2000'],
            'form.no_telp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\-\s]+$/'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.nama_owner' => ['nullable', 'string', 'max:255'],
            'form.alamat' => ['nullable', 'string', 'max:2000'],
            'form.tanggal_dibuat' => ['nullable', 'date'],
            'form.url_website' => ['nullable', 'url:http,https', 'max:255'],
            'form.sub_menu_default' => ['required', 'in:'.implode(',', DataPokok::SUB_MENU_DEFAULTS)],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:1024'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.nama_aplikasi' => 'nama aplikasi',
            'form.keterangan' => 'keterangan',
            'form.no_telp' => 'nomor telepon',
            'form.email' => 'email',
            'form.nama_owner' => 'nama owner',
            'form.alamat' => 'alamat',
            'form.tanggal_dibuat' => 'tanggal dibuat',
            'form.url_website' => 'URL website',
            'form.sub_menu_default' => 'pengaturan sub menu',
            'logo' => 'logo',
            'favicon' => 'favicon',
        ];
    }

    public function buttonReset(): void
    {
        $this->resetValidation();
        $this->reset(['logo', 'favicon']);
        $this->loadForm();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $data = $validated['form'];
        $item = DataPokok::query()->first() ?? new DataPokok(['singleton_key' => 1]);
        Gate::authorize($item->exists ? 'update' : 'create', $item->exists ? $item : DataPokok::class);
        $oldLogo = $item->logo;
        $oldFavicon = $item->favicon;

        if ($this->logo) {
            $data['logo'] = $this->logo->store('data-pokok/logo', 'public');
        }

        if ($this->favicon) {
            $data['favicon'] = $this->favicon->store('data-pokok/favicon', 'public');
        }

        $item->fill($data)->save();

        if ($this->logo && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        if ($this->favicon && $oldFavicon) {
            Storage::disk('public')->delete($oldFavicon);
        }

        $info = $this->id ? 'diperbarui' : 'ditambahkan';
        $this->id = $item->id;
        $this->reset(['logo', 'favicon']);
        $this->resetValidation();
        $this->loadForm();

        $this->dispatch('notify', text: "Data Pokok berhasil {$info}");
        $this->dispatch(
            'favicon-updated',
            url: $item->favicon_url ?? asset('favicon.ico'),
            type: match (strtolower(pathinfo($item->favicon ?? 'favicon.ico', PATHINFO_EXTENSION))) {
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                default => 'image/x-icon',
            },
        );
        $this->dispatch('handle-form');
    }

    private function loadForm(): void
    {
        $item = $this->id ? DataPokok::findOrFail($this->id) : null;

        $this->form = [
            'nama_aplikasi' => $item?->nama_aplikasi,
            'keterangan' => $item?->keterangan,
            'no_telp' => $item?->no_telp,
            'email' => $item?->email,
            'nama_owner' => $item?->nama_owner,
            'alamat' => $item?->alamat,
            'tanggal_dibuat' => $item?->tanggal_dibuat?->format('Y-m-d'),
            'url_website' => $item?->url_website,
            'sub_menu_default' => $item?->sub_menu_default ?? 'hide',
        ];

        $this->logoLama = $item?->logo_url;
        $this->faviconLama = $item?->favicon_url;
    }
};
?>

<div class="card">
    <div class="flex items-center justify-between border-b border-slate-200 p-4 dark:border-navy-500 sm:px-5">
        <div>
            <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">Pengaturan Utama Website</h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-navy-300">Data ini digunakan sebagai identitas umum aplikasi.</p>
        </div>
        <button type="button" wire:click="buttonReset" class="btn size-8 rounded-full p-0 hover:bg-slate-150 focus:bg-slate-300/20 dark:hover:bg-navy-300/20" title="Muat ulang data">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8 8 0 10-2.34 5.66M20 4v7h-7" />
            </svg>
        </button>
    </div>

    <form wire:submit="save" class="space-y-3 p-4 sm:p-5">
        <label class="block">
            <span>Nama Aplikasi <span class="text-error">*</span></span>
            <input
                type="text"
                wire:model="form.nama_aplikasi"
                class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                placeholder="Contoh: Linqo Starter">
            @error('form.nama_aplikasi') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
        </label>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="block">
                <span>Nomor Telepon</span>
                <input type="text" wire:model="form.no_telp" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="+62 812 3456 7890">
                @error('form.no_telp') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span>Email</span>
                <input type="email" wire:model="form.email" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="admin@example.com">
                @error('form.email') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="block">
                <span>Nama Owner</span>
                <input type="text" wire:model="form.nama_owner" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Nama pemilik aplikasi">
                @error('form.nama_owner') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span>Tanggal Dibuat</span>
                <input type="date" wire:model="form.tanggal_dibuat" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent">
                @error('form.tanggal_dibuat') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="block">
            <span>Sub Menu Sidebar Saat Dibuka</span>
            <select wire:model="form.sub_menu_default" class="form-select mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent">
                <option value="hide">Disembunyikan (klik ikon garis untuk membuka)</option>
                <option value="show">Ditampilkan</option>
            </select>
            @error('form.sub_menu_default') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span>URL Website</span>
            <input type="url" wire:model="form.url_website" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="https://example.com">
            @error('form.url_website') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span>Keterangan</span>
            <textarea wire:model="form.keterangan" rows="3" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Keterangan singkat aplikasi"></textarea>
            @error('form.keterangan') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span>Alamat</span>
            <textarea wire:model="form.alamat" rows="3" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent" placeholder="Alamat lengkap"></textarea>
            @error('form.alamat') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
        </label>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="block">
                <span>Logo</span>
                <input type="file" wire:model="logo" accept=".jpg,.jpeg,.png,.webp,.svg" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 text-xs hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent">
                <span class="mt-1 block text-xs text-slate-400">JPG, PNG, WebP, atau SVG. Maks. 2 MB.</span>
                @error('logo') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" class="mt-2 h-16 max-w-full rounded object-contain" alt="Pratinjau logo">
                @elseif ($logoLama)
                    <img src="{{ $logoLama }}" class="mt-2 h-16 max-w-full rounded object-contain" alt="Logo saat ini">
                @endif
            </label>

            <label class="block">
                <span>Favicon</span>
                <input type="file" wire:model="favicon" accept=".ico,.png,.svg" class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 text-xs hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent">
                <span class="mt-1 block text-xs text-slate-400">ICO, PNG, atau SVG. Maks. 1 MB.</span>
                @error('favicon') <span class="text-tiny-plus text-error">{{ $message }}</span> @enderror
                @if ($favicon)
                    <img src="{{ $favicon->temporaryUrl() }}" class="mt-2 size-10 rounded object-contain" alt="Pratinjau favicon">
                @elseif ($faviconLama)
                    <img src="{{ $faviconLama }}" class="mt-2 size-10 rounded object-contain" alt="Favicon saat ini">
                @endif
            </label>
        </div>

        <div class="mt-2 border-t border-slate-200 pt-3 text-right dark:border-navy-500">
            @can($id ? 'update' : 'create', $id ? Modules\Superadmin\Models\DataPokok::find($id) : Modules\Superadmin\Models\DataPokok::class)
            <button type="submit" wire:loading.attr="disabled" wire:target="save,logo,favicon" class="btn bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 disabled:opacity-50 dark:bg-accent dark:hover:bg-accent-focus">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 4h10l4 4v10a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2zm2 0v5h8V4m-6 11h4" />
                </svg>
                Simpan
            </button>
            @endcan
            <button type="button" wire:click="buttonReset" class="btn bg-slate-150 font-medium text-slate-700 hover:bg-slate-200 focus:bg-slate-200 active:bg-slate-200/80 dark:bg-navy-500 dark:text-navy-100 dark:hover:bg-navy-450">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-restore size-5"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M3.06 13a9 9 0 1 0 .49 -4.087" /><path d="M3 4.001v5h5" /><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>
                Reset
            </button>
        </div>
    </form>
</div>
