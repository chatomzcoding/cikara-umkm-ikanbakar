<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksPermissions
{
    protected function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('superadmin') || $user->hasPermission($permission);
    }
}
