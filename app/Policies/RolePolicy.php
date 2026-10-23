<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RolePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->allowed($user, 'roles.view');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return ! $role->isSystem() && $this->allowed($user, 'roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return ! $role->isSystem() && $this->allowed($user, 'roles.delete');
    }

    public function assignPermissions(User $user, Role $role): bool
    {
        return ! $role->isSystem() && $this->allowed($user, 'roles.assign-permissions');
    }
}
