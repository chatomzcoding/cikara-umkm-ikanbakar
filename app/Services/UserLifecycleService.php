<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserLifecycleService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function suspend(User $target, User $actor, string $reason): User
    {
        return $this->transition($target, $actor, UserStatus::Suspended, $reason);
    }

    public function deactivate(User $target, User $actor, string $reason): User
    {
        return $this->transition($target, $actor, UserStatus::Inactive, $reason);
    }

    public function activate(User $target, User $actor): User
    {
        return $this->transition($target, $actor, UserStatus::Active);
    }

    private function transition(User $target, User $actor, UserStatus $newStatus, ?string $reason = null): User
    {
        return DB::transaction(function () use ($target, $actor, $newStatus, $reason): User {
            $target = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $oldStatus = $target->status;

            if ($oldStatus === $newStatus) {
                throw ValidationException::withMessages([
                    'status' => "Pengguna sudah berstatus {$newStatus->label()}.",
                ]);
            }

            if ($newStatus !== UserStatus::Active) {
                $this->guardDeactivation($target, $actor);
                $reason = trim((string) $reason);

                if ($reason === '') {
                    throw ValidationException::withMessages(['status' => 'Alasan wajib diisi.']);
                }
            }

            $target->status = $newStatus;

            if ($newStatus === UserStatus::Active) {
                $target->activated_at = now();
                $target->suspended_at = null;
                $target->suspended_by = null;
                $target->suspension_reason = null;
                $target->deactivated_at = null;
                $target->deactivated_by = null;
                $target->deactivation_reason = null;
            } elseif ($newStatus === UserStatus::Suspended) {
                $target->suspended_at = now();
                $target->suspended_by = $actor->getKey();
                $target->suspension_reason = $reason;
                $target->deactivated_at = null;
                $target->deactivated_by = null;
                $target->deactivation_reason = null;
            } else {
                $target->deactivated_at = now();
                $target->deactivated_by = $actor->getKey();
                $target->deactivation_reason = $reason;
                $target->suspended_at = null;
                $target->suspended_by = null;
                $target->suspension_reason = null;
            }

            $target->save();

            $event = match ($newStatus) {
                UserStatus::Active => 'user_activated',
                UserStatus::Suspended => 'user_suspended',
                UserStatus::Inactive => 'user_deactivated',
            };

            $this->auditLogger->record(
                event: $event,
                description: "Status pengguna diubah menjadi {$newStatus->label()}",
                subject: $target,
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => $newStatus->value],
                metadata: array_filter(['reason' => $reason]),
                actor: $actor,
            );

            return $target->refresh();
        });
    }

    private function guardDeactivation(User $target, User $actor): void
    {
        if ($target->is($actor)) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat menangguhkan atau menonaktifkan akun yang sedang digunakan.',
            ]);
        }

        if (! $target->hasRole('superadmin')) {
            return;
        }

        $activeSuperadminIds = User::query()
            ->where('status', UserStatus::Active->value)
            ->whereHas('roles', fn ($query) => $query->where('name', 'superadmin'))
            ->lockForUpdate()
            ->pluck('users.id');

        if ($activeSuperadminIds->count() <= 1) {
            throw ValidationException::withMessages([
                'status' => 'Super Admin aktif terakhir tidak dapat ditangguhkan atau dinonaktifkan.',
            ]);
        }
    }
}
