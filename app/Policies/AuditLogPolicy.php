<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AuditLogPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'audit-logs.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->allowed($user, 'audit-logs.view');
    }
}
