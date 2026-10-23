<?php

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\NotificationService;

new class extends Component
{
    use PasswordValidationRules;
    use WithFileUploads;

    public string $name = '';

    public $photo;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = $this->user->name;
    }

    #[Computed]
    public function user(): User
    {
        return auth()->user()->loadMissing('roles');
    }

    #[Computed]
    public function roleNames(): string
    {
        return $this->user->roles
            ->map(fn ($role) => $role->display_name ?: $role->name)
            ->filter()
            ->join(', ') ?: 'User';
    }

    public function updateProfile(NotificationService $notifications): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'photo.image' => 'Foto profil harus berupa gambar.',
            'photo.mimes' => 'Foto profil harus berformat JPG, JPEG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto profil maksimal 5 MB.',
        ]);

        $user = auth()->user();
        $oldPhoto = $user->profile_photo_path;
        $newPhoto = null;

        try {
            if ($this->photo) {
                $newPhoto = $this->photo->store('user-profiles', 'public');
            }

            $user->forceFill([
                'name' => $validated['name'],
                'profile_photo_path' => $newPhoto ?: $oldPhoto,
            ])->save();

            if ($newPhoto && $oldPhoto && str_starts_with($oldPhoto, 'user-profiles/')) {
                Storage::disk('public')->delete($oldPhoto);
            }
        } catch (Throwable $exception) {
            if ($newPhoto) {
                Storage::disk('public')->delete($newPhoto);
            }

            throw $exception;
        }

        // Alias dengan hasil yang sama:
        $notifications->log($user, 'User Edit', 'User berhasil diperbaharui.');

        $this->reset('photo');
        unset($this->user, $this->roleNames);

        $this->dispatch('notify', text: 'Profil berhasil diperbarui', variant: 'success');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ]);

        $user = auth()->user();
        $user->forceFill(['password' => $this->password])->save();

        session()->put(
            'password_hash_'.Auth::getDefaultDriver(),
            $user->getAuthPassword(),
        );

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->resetValidation();

        $this->dispatch('notify', text: 'Password berhasil diperbarui', variant: 'success');
    }
};
?>

<div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
    <aside class="col-span-12 space-y-4 lg:col-span-4">
        <section class="card overflow-hidden">
            <div class="bg-gradient-to-br from-primary/20 via-info/10 to-transparent p-5 text-center">
                <div class="mx-auto size-28 overflow-hidden rounded-full border-4 border-white bg-slate-200 shadow-lg dark:border-navy-700 dark:bg-navy-600">
                    @if ($photo)
                        <img
                            class="size-full object-cover"
                            src="{{ $photo->temporaryUrl() }}"
                            alt="Pratinjau foto profil"
                        >
                    @else
                        <img
                            class="size-full object-cover"
                            src="{{ $this->user->getPhoto() }}"
                            alt="Foto {{ $this->user->name }}"
                        >
                    @endif
                </div>

                <h2 class="mt-4 text-xl font-semibold text-slate-800 dark:text-navy-50">
                    {{ $this->user->name }}
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-navy-300">{{ $this->user->email }}</p>
                <span class="mt-3 inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary dark:text-accent-light">
                    {{ $this->roleNames }}
                </span>
            </div>

            <div class="space-y-4 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-info/10 text-info">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">ID User</p>
                        <p class="font-medium text-slate-700 dark:text-navy-100">#{{ $this->user->id }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-success/10 text-success">
                        <i class="fa-solid fa-envelope-circle-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Status email</p>
                        <p class="font-medium {{ $this->user->email_verified_at ? 'text-success' : 'text-warning' }}">
                            {{ $this->user->email_verified_at ? 'Terverifikasi' : 'Belum terverifikasi' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-warning/10 text-warning">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Terdaftar sejak</p>
                        <p class="font-medium text-slate-700 dark:text-navy-100">
                            {{ $this->user->created_at?->translatedFormat('d F Y, H:i') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Data terakhir diperbarui</p>
                        <p class="font-medium text-slate-700 dark:text-navy-100">
                            {{ $this->user->updated_at?->diffForHumans() }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </aside>

    <div class="col-span-12 space-y-4 lg:col-span-8">
        <section class="card p-4 sm:p-5">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-4 dark:border-navy-500">
                <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary dark:text-accent-light">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <h2 class="font-medium text-slate-700 dark:text-navy-100">Informasi Profil</h2>
                    <p class="text-xs text-slate-400">Perbarui nama dan foto yang tampil pada akun.</p>
                </div>
            </div>

            <form class="mt-5 space-y-4" wire:submit="updateProfile">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Nama lengkap</span>
                    <input
                        class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 placeholder:text-slate-400/70 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                        type="text"
                        wire:model="name"
                        autocomplete="name"
                    >
                    @error('name')
                        <span class="mt-1 block text-tiny-plus text-error">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Email</span>
                    <input
                        class="form-input mt-1.5 w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-slate-500 dark:border-navy-500 dark:bg-navy-600"
                        type="email"
                        value="{{ $this->user->email }}"
                        disabled
                    >
                    <span class="mt-1 block text-xs text-slate-400">Email hanya ditampilkan sebagai informasi akun.</span>
                </label>

                <div
                    x-data="{ uploading: false, progress: 0 }"
                    x-on:livewire-upload-start="uploading = true; progress = 0"
                    x-on:livewire-upload-finish="uploading = false; progress = 100"
                    x-on:livewire-upload-cancel="uploading = false; progress = 0"
                    x-on:livewire-upload-error="uploading = false; progress = 0"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                >
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Foto profil</span>
                        <input
                            class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 dark:border-navy-450"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            wire:model="photo"
                        >
                        <span class="mt-1 block text-xs text-slate-400">JPG, PNG, atau WebP. Maksimal 5 MB.</span>
                    </label>

                    <div x-cloak x-show="uploading" class="mt-2">
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-navy-500">
                            <div class="h-full rounded-full bg-info transition-[width] duration-150" :style="`width: ${progress}%`"></div>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-info">
                            <span>Mengunggah foto</span>
                            <span x-text="`${progress}%`"></span>
                        </div>
                    </div>

                    @error('photo')
                        <span class="mt-1 block text-tiny-plus text-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button
                        class="btn min-w-36 bg-primary font-medium text-white hover:bg-primary-focus disabled:cursor-wait disabled:opacity-70"
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="updateProfile,photo"
                    >
                        <span wire:loading.remove wire:target="updateProfile">Simpan Profil</span>
                        <span wire:loading wire:target="updateProfile">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="card p-4 sm:p-5">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-4 dark:border-navy-500">
                <div class="flex size-10 items-center justify-center rounded-xl bg-warning/10 text-warning">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h2 class="font-medium text-slate-700 dark:text-navy-100">Reset Password</h2>
                    <p class="text-xs text-slate-400">Gunakan password yang kuat dan tidak dipakai pada layanan lain.</p>
                </div>
            </div>

            <form class="mt-5 space-y-4" wire:submit="updatePassword">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Password saat ini</span>
                    <input
                        class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 focus:border-primary dark:border-navy-450 dark:focus:border-accent"
                        type="password"
                        wire:model="current_password"
                        autocomplete="current-password"
                    >
                    @error('current_password')
                        <span class="mt-1 block text-tiny-plus text-error">{{ $message }}</span>
                    @enderror
                </label>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Password baru</span>
                        <input
                            class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 focus:border-primary dark:border-navy-450 dark:focus:border-accent"
                            type="password"
                            wire:model="password"
                            autocomplete="new-password"
                        >
                        @error('password')
                            <span class="mt-1 block text-tiny-plus text-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Konfirmasi password baru</span>
                        <input
                            class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 focus:border-primary dark:border-navy-450 dark:focus:border-accent"
                            type="password"
                            wire:model="password_confirmation"
                            autocomplete="new-password"
                        >
                    </label>
                </div>

                <div class="flex justify-end">
                    <button
                        class="btn min-w-36 bg-warning font-medium text-white hover:bg-warning-focus disabled:cursor-wait disabled:opacity-70"
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="updatePassword"
                    >
                        <span wire:loading.remove wire:target="updatePassword">Reset Password</span>
                        <span wire:loading wire:target="updatePassword">Memproses...</span>
                    </button>
                </div>
            </form>
        </section>

        @if (\Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            <section class="card flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary dark:text-accent-light">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <div>
                        <h2 class="font-medium text-slate-700 dark:text-navy-100">Autentikasi Dua Langkah</h2>
                        <p class="text-xs text-slate-400">
                            Status: {{ $this->user->hasEnabledTwoFactorAuthentication() ? 'Aktif' : 'Nonaktif' }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('two-factor.show') }}" class="btn shrink-0 bg-primary font-medium text-white hover:bg-primary-focus">
                    Kelola
                </a>
            </section>
        @endif
    </div>
</div>
