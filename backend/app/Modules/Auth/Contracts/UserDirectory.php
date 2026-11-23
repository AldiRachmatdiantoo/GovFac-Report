<?php

namespace App\Modules\Auth\Contracts;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Support\Collection;

/**
 * Looks up accounts for modules that address users by role, such as Notification.
 */
interface UserDirectory
{
    /**
     * @return Collection<int, User>
     */
    public function withRole(UserRole $role): Collection;
}
