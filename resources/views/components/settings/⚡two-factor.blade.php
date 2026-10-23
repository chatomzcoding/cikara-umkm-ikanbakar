<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Livewire\Component;

new class extends Component
{
    public bool $twoFactorEnabled = false;

    public bool $showVerificationStep = false;

    public string $qrCodeSvg = '';

    public string $manualSetupKey = '';

    public array $recoveryCodes = [];

    public string $code = '';

    public function mount(DisableTwoFactorAuthentication $disable): void
    {
        abort_unless(Features::canManageTwoFactorAuthentication(), 403);

        $user = auth()->user();

        // Setup yang ditinggalkan sebelum dikonfirmasi tidak boleh tetap aktif setengah jalan.
        if ($this->requiresConfirmation() && $user->two_factor_secret && is_null($user->two_factor_confirmed_at)) {
            $disable($user);
        }

        $this->twoFactorEnabled = $user->fresh()->hasEnabledTwoFactorAuthentication();

        if ($this->twoFactorEnabled) {
            $this->loadRecoveryCodes();
        }
    }

    public function enable(EnableTwoFactorAuthentication $enable): void
    {
        if (! $this->ensurePasswordConfirmed()) {
            return;
        }

        $enable(auth()->user());

        if ($this->requiresConfirmation()) {
            $user = auth()->user()->fresh();
            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
            $this->showVerificationStep = true;

            return;
        }

        $this->twoFactorEnabled = true;
        $this->loadRecoveryCodes();
        $this->dispatch('notify', text: 'Autentikasi dua langkah diaktifkan', variant: 'success');
    }

    public function confirm(ConfirmTwoFactorAuthentication $confirm): void
    {
        if (! $this->ensurePasswordConfirmed()) {
            return;
        }

        $this->validate(
            ['code' => ['required', 'string', 'digits:6']],
            ['code.required' => 'Kode wajib diisi.', 'code.digits' => 'Kode harus 6 digit angka.'],
        );

        $confirm(auth()->user(), $this->code);

        $this->reset('code', 'qrCodeSvg', 'manualSetupKey', 'showVerificationStep');
        $this->twoFactorEnabled = true;
        $this->loadRecoveryCodes();
        $this->dispatch('notify', text: 'Autentikasi dua langkah diaktifkan', variant: 'success');
    }

    public function cancelSetup(DisableTwoFactorAuthentication $disable): void
    {
        $disable(auth()->user());
        $this->reset('code', 'qrCodeSvg', 'manualSetupKey', 'showVerificationStep');
        $this->resetValidation();
    }

    public function disable(DisableTwoFactorAuthentication $disable): void
    {
        if (! $this->ensurePasswordConfirmed()) {
            return;
        }

        $disable(auth()->user());
        $this->twoFactorEnabled = false;
        $this->recoveryCodes = [];
        $this->dispatch('notify', text: 'Autentikasi dua langkah dinonaktifkan', variant: 'success');
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        if (! $this->ensurePasswordConfirmed()) {
            return;
        }

        $generate(auth()->user());
        $this->loadRecoveryCodes();
        $this->dispatch('notify', text: 'Kode pemulihan baru sudah dibuat', variant: 'success');
    }

    private function loadRecoveryCodes(): void
    {
        $this->recoveryCodes = auth()->user()->fresh()->recoveryCodes();
    }

    private function requiresConfirmation(): bool
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    private function ensurePasswordConfirmed(): bool
    {
        if (! Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            return true;
        }

        $confirmedAt = (int) session('auth.password_confirmed_at', 0);

        if (time() - $confirmedAt < (int) config('auth.password_timeout', 10800)) {
            return true;
        }

        session()->put('url.intended', route('two-factor.show'));
        $this->redirectRoute('password.confirm');

        return false;
    }
};
?>

<section class="card p-4 sm:p-5">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4 dark:border-navy-500">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary dark:text-accent-light">
                <i class="fa-solid fa-mobile-screen-button"></i>
            </div>
            <div>
                <h2 class="font-medium text-slate-700 dark:text-navy-100">Autentikasi Dua Langkah</h2>
                <p class="text-xs text-slate-400">Tambahan kode dari aplikasi authenticator saat login.</p>
            </div>
        </div>
        <span class="badge {{ $twoFactorEnabled ? 'bg-success/10 text-success' : 'bg-slate-150 text-slate-500 dark:bg-navy-500 dark:text-navy-100' }}">
            {{ $twoFactorEnabled ? 'Aktif' : 'Nonaktif' }}
        </span>
    </div>

    @if ($showVerificationStep)
        <div class="mt-5 space-y-4">
            <p class="text-sm text-slate-600 dark:text-navy-200">
                Pindai QR code berikut dengan aplikasi authenticator (Google Authenticator, Authy, dan sejenisnya),
                lalu masukkan kode 6 digit yang muncul.
            </p>
            <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                <div class="rounded-lg bg-white p-3">{!! $qrCodeSvg !!}</div>
                <div class="text-sm">
                    <p class="text-xs text-slate-400">Atau masukkan kunci ini secara manual:</p>
                    <p class="mt-1 break-all font-mono font-medium text-slate-700 dark:text-navy-100">{{ $manualSetupKey }}</p>
                </div>
            </div>

            <form wire:submit="confirm" class="space-y-4">
                <label class="block max-w-xs">
                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">Kode verifikasi</span>
                    <input
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        wire:model="code"
                        class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 focus:border-primary dark:border-navy-450 dark:focus:border-accent"
                    >
                    @error('code')
                        <span class="mt-1 block text-tiny-plus text-error">{{ $message }}</span>
                    @enderror
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelSetup" class="btn bg-slate-150 font-medium text-slate-700 hover:bg-slate-200 dark:bg-navy-500 dark:text-navy-100">
                        Batal
                    </button>
                    <button type="submit" class="btn bg-primary font-medium text-white hover:bg-primary-focus">
                        Konfirmasi
                    </button>
                </div>
            </form>
        </div>
    @elseif ($twoFactorEnabled)
        <div class="mt-5 space-y-4">
            <p class="text-sm text-slate-600 dark:text-navy-200">
                Simpan kode pemulihan di tempat aman. Setiap kode hanya bisa dipakai sekali jika Anda kehilangan akses ke aplikasi authenticator.
            </p>
            <div class="grid grid-cols-2 gap-2 rounded-lg bg-slate-100 p-4 font-mono text-sm text-slate-700 dark:bg-navy-800 dark:text-navy-100">
                @foreach ($recoveryCodes as $recoveryCode)
                    <span>{{ $recoveryCode }}</span>
                @endforeach
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="regenerateRecoveryCodes" class="btn bg-slate-150 font-medium text-slate-700 hover:bg-slate-200 dark:bg-navy-500 dark:text-navy-100">
                    Buat Ulang Kode Pemulihan
                </button>
                <button
                    type="button"
                    wire:click="disable"
                    wire:confirm="Nonaktifkan autentikasi dua langkah?"
                    class="btn bg-error font-medium text-white hover:bg-error-focus"
                >
                    Nonaktifkan
                </button>
            </div>
        </div>
    @else
        <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600 dark:text-navy-200">
                Saat aktif, login membutuhkan password dan kode dari aplikasi authenticator di ponsel Anda.
            </p>
            <button type="button" wire:click="enable" class="btn shrink-0 bg-primary font-medium text-white hover:bg-primary-focus">
                Aktifkan
            </button>
        </div>
    @endif
</section>
