<?php

namespace Modules\Superadmin\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use Modules\Superadmin\Models\DataPokok;

class DataPokokPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'data-pokok.view');
    }

    public function view(User $user, DataPokok $model): bool
    {
        return $this->allowed($user, 'data-pokok.view');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'data-pokok.update');
    }

    public function update(User $user, DataPokok $model): bool
    {
        return $this->allowed($user, 'data-pokok.update');
    }
}
