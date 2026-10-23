<?php

namespace Modules\Superadmin\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use Modules\Superadmin\Models\Sistemkategorisub;

class SistemkategorisubPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'subcategories.view');
    }

    public function view(User $user, Sistemkategorisub $model): bool
    {
        return $this->allowed($user, 'subcategories.view');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'subcategories.create');
    }

    public function update(User $user, Sistemkategorisub $model): bool
    {
        return $this->allowed($user, 'subcategories.update');
    }

    public function delete(User $user, Sistemkategorisub $model): bool
    {
        return $this->allowed($user, 'subcategories.delete');
    }
}
