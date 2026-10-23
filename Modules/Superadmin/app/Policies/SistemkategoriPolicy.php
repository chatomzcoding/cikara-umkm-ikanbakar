<?php

namespace Modules\Superadmin\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use Modules\Superadmin\Models\Sistemkategori;

class SistemkategoriPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'categories.view');
    }

    public function view(User $user, Sistemkategori $model): bool
    {
        return $this->allowed($user, 'categories.view');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'categories.create');
    }

    public function update(User $user, Sistemkategori $model): bool
    {
        return $this->allowed($user, 'categories.update');
    }

    public function delete(User $user, Sistemkategori $model): bool
    {
        return $this->allowed($user, 'categories.delete');
    }
}
