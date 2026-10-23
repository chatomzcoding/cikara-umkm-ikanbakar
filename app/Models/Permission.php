<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Laratrust\Models\Permission as PermissionModel;

class Permission extends PermissionModel
{
    use Auditable;

    public $guarded = [];
}
