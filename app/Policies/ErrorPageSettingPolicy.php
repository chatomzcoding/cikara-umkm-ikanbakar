<?php

namespace App\Policies;

use App\Models\ErrorPageSetting;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ErrorPageSettingPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'error-pages.view');
    }

    public function update(User $user, ErrorPageSetting $setting): bool
    {
        return $this->allowed($user, 'error-pages.update');
    }
}
