<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserImpersonationService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function start(User $actor, User $target): void
    {
        if (session()->has('impersonation.impersonator_id')) {
            throw ValidationException::withMessages([
                'impersonation' => 'Selesaikan impersonasi yang sedang berjalan terlebih dahulu.',
            ]);
        }

        if ($actor->is($target)) {
            throw ValidationException::withMessages([
                'impersonation' => 'Anda tidak dapat melakukan impersonasi ke akun sendiri.',
            ]);
        }

        if (! $target->isActive()) {
            throw ValidationException::withMessages([
                'impersonation' => 'Hanya pengguna aktif yang dapat diimpersonasi.',
            ]);
        }

        if ($target->hasRole('superadmin') && ! $actor->hasRole('superadmin')) {
            throw ValidationException::withMessages([
                'impersonation' => 'Hanya Super Admin yang dapat mengimpersonasi Super Admin lain.',
            ]);
        }

        $this->auditLogger->record(
            event: 'impersonation_started',
            description: "Mulai impersonasi pengguna {$target->name}",
            subject: $target,
            metadata: ['impersonator_id' => $actor->getKey()],
            actor: $actor,
        );

        session()->put([
            'impersonation.impersonator_id' => $actor->getKey(),
            'impersonation.impersonator_name' => $actor->name,
            'impersonation.started_at' => now()->toIso8601String(),
        ]);

        Auth::guard('web')->login($target);
        session()->regenerate();
    }

    public function stop(): ?User
    {
        $impersonatorId = session('impersonation.impersonator_id');

        if (! $impersonatorId) {
            return Auth::user();
        }

        $target = Auth::user();
        $impersonator = User::withTrashed()->find($impersonatorId);

        if (! $impersonator || $impersonator->trashed() || ! $impersonator->isActive()) {
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();

            return null;
        }

        $this->auditLogger->record(
            event: 'impersonation_ended',
            description: "Selesai impersonasi pengguna {$target?->name}",
            subject: $target,
            metadata: ['impersonator_id' => $impersonator->getKey()],
            actor: $impersonator,
        );

        Auth::guard('web')->login($impersonator);
        session()->forget('impersonation');
        session()->regenerate();

        return $impersonator;
    }
}
