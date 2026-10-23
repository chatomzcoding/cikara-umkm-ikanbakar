<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->loadMissing('roles');
    }

    #[Computed]
    public function roleName(): string
    {
        return $this->user->roles
            ->map(fn (Role $role) => $role->display_name ?: str($role->name)->headline())
            ->filter()
            ->join(', ') ?: 'Pengguna';
    }

    #[Computed]
    public function greeting(): string
    {
        return match (true) {
            now()->hour < 11 => 'Selamat pagi',
            now()->hour < 15 => 'Selamat siang',
            now()->hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }

    #[Computed]
    public function isSuperadmin(): bool
    {
        return $this->user->hasRole('superadmin');
    }

    #[Computed]
    public function userCount(): int
    {
        return $this->isSuperadmin ? User::count() : 0;
    }

    #[Computed]
    public function roleCount(): int
    {
        return $this->isSuperadmin ? Role::count() : 0;
    }

    #[Computed]
    public function verifiedUserCount(): int
    {
        return $this->isSuperadmin
            ? User::whereNotNull('email_verified_at')->count()
            : 0;
    }
};
?>

<div class="py-6 sm:py-8">
    {{-- Welcome banner --}}
    <section class="relative overflow-hidden rounded-2xl bg-linear-to-r from-primary to-primary-focus px-5 py-6 text-slate-900 shadow-lg shadow-primary/20 sm:px-7 sm:py-8 dark:text-white">
        <div class="pointer-events-none absolute -right-16 -top-20 size-56 rounded-full bg-white/10 dark:bg-white/10"></div>
        <div class="pointer-events-none absolute -bottom-24 right-24 size-44 rounded-full bg-white/10 dark:bg-white/10"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-slate-900/90 backdrop-blur-sm dark:text-white/90">
                    <span class="size-2 rounded-full bg-success"></span>
                    {{ now()->translatedFormat('l, d F Y') }}
                </div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl text-slate-950 dark:text-white">
                    {{ $this->greeting }}, {{ str($this->user->name)->before(' ') }}!
                </h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-700/90 sm:text-base dark:text-slate-300">
                    Selamat datang kembali. Berikut ringkasan akun dan akses Anda hari ini.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-3 rounded-xl border border-white/15 bg-white/10 p-3 backdrop-blur-sm">
                <div class="flex size-12 items-center justify-center rounded-xl bg-white text-lg font-semibold uppercase text-primary shadow-sm">
                    {{ $this->user->initials() }}
                </div>
                <div class="min-w-0 pr-2">
                    <p class="max-w-48 truncate font-semibold">{{ $this->user->name }}</p>
                    <p class="max-w-48 truncate text-xs text-white/70">{{ $this->roleName }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Account summary --}}
    <section class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card flex items-center gap-4 p-4 sm:p-5">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0A17.9 17.9 0 0112 21.75a17.9 17.9 0 01-7.5-1.65z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-slate-400 dark:text-navy-300">Role aktif</p>
                <p class="mt-1 truncate font-semibold text-slate-700 dark:text-navy-100">{{ $this->roleName }}</p>
            </div>
        </div>

        <div class="card flex items-center gap-4 p-4 sm:p-5">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5A2.25 2.25 0 0119.5 19.5h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-8.69 5.52a2 2 0 01-2.12 0L2.25 6.75" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-slate-400 dark:text-navy-300">Status email</p>
                <p class="mt-1 font-semibold text-slate-700 dark:text-navy-100">
                    {{ $this->user->email_verified_at ? 'Terverifikasi' : 'Belum terverifikasi' }}
                </p>
            </div>
        </div>

        <div class="card flex items-center gap-4 p-4 sm:p-5">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-info/10 text-info">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-slate-400 dark:text-navy-300">Bergabung sejak</p>
                <p class="mt-1 font-semibold text-slate-700 dark:text-navy-100">{{ $this->user->created_at->translatedFormat('d M Y') }}</p>
            </div>
        </div>

        <div class="card flex items-center gap-4 p-4 sm:p-5">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-warning/10 text-warning">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.04a11.96 11.96 0 01-8.25 3.29c0 5.59 3.82 10.29 8.99 11.62a12 12 0 007.51-11.62A11.96 11.96 0 0112 2.71z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-slate-400 dark:text-navy-300">Keamanan 2 langkah</p>
                <p class="mt-1 font-semibold text-slate-700 dark:text-navy-100">
                    {{ $this->user->two_factor_confirmed_at ? 'Aktif' : 'Belum aktif' }}
                </p>
            </div>
        </div>
    </section>

    @if ($this->isSuperadmin)
        {{-- System overview is only shown to the superadmin role. --}}
        <section class="mt-5">
            <div class="mb-3">
                <h2 class="text-base font-semibold text-slate-800 dark:text-navy-100">Ringkasan sistem</h2>
                <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">Informasi umum yang hanya tersedia untuk Super Admin.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-500 dark:text-navy-200">Total pengguna</p>
                        <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.1 9.1 0 003.74-.48 3 3 0 00-4.68-2.72m.94 3.2v-.01c0-1.16-.3-2.25-.83-3.19m.83 3.2v.13A12 12 0 0112 20.25c-2.17 0-4.2-.57-5.96-1.56m0 0A5.98 5.98 0 0112 13.5a5.98 5.98 0 015.17 2.02M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-slate-800 dark:text-navy-100">{{ number_format($this->userCount) }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">Akun terdaftar</p>
                </div>

                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-500 dark:text-navy-200">Email terverifikasi</p>
                        <span class="flex size-9 items-center justify-center rounded-lg bg-success/10 text-success">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m5.25 2.25a8.25 8.25 0 11-16.5 0 8.25 8.25 0 0116.5 0z" /></svg>
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-slate-800 dark:text-navy-100">{{ number_format($this->verifiedUserCount) }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">Pengguna aktif terverifikasi</p>
                </div>

                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-500 dark:text-navy-200">Role tersedia</p>
                        <span class="flex size-9 items-center justify-center rounded-lg bg-info/10 text-info">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75a6 6 0 00-6 6v3.75a2.25 2.25 0 01-.66 1.59L4.5 15.94h15l-.84-.85A2.25 2.25 0 0118 13.5V9.75a6 6 0 00-6-6zM9.75 18.75a2.25 2.25 0 004.5 0" /></svg>
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-slate-800 dark:text-navy-100">{{ number_format($this->roleCount) }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">Hak akses dikonfigurasi</p>
                </div>
            </div>
        </section>
    @endif

    <section class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- User information --}}
        <div class="card lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-150 px-5 py-4 dark:border-navy-500 sm:px-6">
                <div>
                    <h2 class="font-semibold text-slate-800 dark:text-navy-100">Informasi pengguna</h2>
                    <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">Data utama akun yang sedang digunakan.</p>
                </div>
                <a href="{{ route('user.settings') }}" wire:navigate class="btn rounded-lg bg-primary/10 px-3 py-2 text-xs font-medium text-primary hover:bg-primary/20 focus:bg-primary/20 dark:bg-accent/15 dark:text-accent-light">
                    Edit profil
                </a>
            </div>

            <div class="p-5 sm:p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="flex size-20 shrink-0 items-center justify-center rounded-2xl bg-slate-150 text-2xl font-semibold uppercase text-slate-600 dark:bg-navy-500 dark:text-navy-100">
                        {{ $this->user->initials() }}
                    </div>
                    <div class="min-w-0">
                        <h3 class="truncate text-xl font-semibold text-slate-800 dark:text-navy-100">{{ $this->user->name }}</h3>
                        <p class="mt-1 truncate text-sm text-slate-500 dark:text-navy-200">{{ $this->user->email }}</p>
                        <span class="mt-3 inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary dark:bg-accent/15 dark:text-accent-light">
                            {{ $this->roleName }}
                        </span>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-4 border-t border-slate-150 pt-5 dark:border-navy-500 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-slate-400 dark:text-navy-300">ID pengguna</dt>
                        <dd class="mt-1 font-medium text-slate-700 dark:text-navy-100">#{{ $this->user->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 dark:text-navy-300">Tanggal bergabung</dt>
                        <dd class="mt-1 font-medium text-slate-700 dark:text-navy-100">{{ $this->user->created_at->translatedFormat('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 dark:text-navy-300">Terakhir diperbarui</dt>
                        <dd class="mt-1 font-medium text-slate-700 dark:text-navy-100">{{ $this->user->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Edit profile only --}}
        <div class="card p-5 sm:p-6">
            <h2 class="font-semibold text-slate-800 dark:text-navy-100">Pengaturan profil</h2>
            <p class="mt-1 text-xs text-slate-400 dark:text-navy-300">Perbarui informasi akun Anda melalui halaman profil.</p>

            <div class="mt-5">
                <a href="{{ route('user.settings') }}" class="group flex items-center justify-between rounded-2xl border border-slate-150 bg-slate-50 px-4 py-4 text-slate-700 transition hover:border-primary/30 hover:bg-white dark:border-navy-500 dark:bg-navy-800 dark:text-navy-100 dark:hover:border-accent/30 dark:hover:bg-navy-700">
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">Edit profil</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-navy-300">Ubah nama, alamat email, dan verifikasi Anda.</p>
                    </div>
                    <svg class="h-5 w-5 text-slate-400 transition-transform group-hover:translate-x-1 dark:text-navy-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
</div>
