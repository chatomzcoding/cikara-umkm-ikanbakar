<?php

namespace Modules\Data\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use Modules\Data\Models\Siswa;

class SiswaPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'data.siswas.view');
    }

    public function view(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'data.siswas.view');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'data.siswas.create');
    }

    public function update(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'data.siswas.update');
    }

    public function delete(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'data.siswas.delete');
    }

    public function restore(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'data.siswas.restore');
    }

    public function forceDelete(User $user, Siswa $model): bool
    {
        return $this->allowed($user, 'data.siswas.force-delete');
    }
}
