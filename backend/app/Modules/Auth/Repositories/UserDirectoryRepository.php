<?php

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Database\Eloquent\Collection;

class UserDirectoryRepository
{
    /**
     * @return Collection<int, User>
     */
    public function withRole(UserRole $role): Collection
    {
        return User::query()->where('role', $role)->orderBy('id')->get();
    }
}
