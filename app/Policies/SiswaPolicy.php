<?php

namespace App\Policies;

use App\Models\Siswa;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SiswaPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'siswas.view');
    }

    public function view(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'siswas.view');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'siswas.create');
    }

    public function update(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'siswas.update');
    }

    public function delete(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'siswas.delete');
    }

    public function restore(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'siswas.restore');
    }

    public function forceDelete(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'siswas.force-delete');
    }
}
