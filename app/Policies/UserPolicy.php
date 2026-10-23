<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class UserPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.view');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.update');
    }

    public function delete(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.delete');
    }

    public function restore(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.restore');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.force-delete');
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.assign-roles');
    }

    public function suspend(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.suspend');
    }

    public function activate(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.activate');
    }

    public function deactivate(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.deactivate');
    }

    public function impersonate(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.impersonate');
    }
}
