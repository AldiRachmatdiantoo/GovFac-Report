<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Contracts\UserDirectory;
use App\Modules\Auth\Repositories\UserDirectoryRepository;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Support\Collection;

class EloquentUserDirectory implements UserDirectory
{
    public function __construct(private readonly UserDirectoryRepository $users) {}

    public function withRole(UserRole $role): Collection
    {
        return $this->users->withRole($role)->toBase();
    }
}
