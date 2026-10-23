<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Laratrust\Models\Role as RoleModel;

class Role extends RoleModel
{
    use Auditable;

    public const SYSTEM_ROLES = ['superadmin'];

    public $guarded = [];

    public function isSystem(): bool
    {
        return in_array($this->name, self::SYSTEM_ROLES, true);
    }
}
