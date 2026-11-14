<?php

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Enums\UserRole;

class UserRepository
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function createCitizen(array $data): User
    {
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        // role tidak fillable; registrasi mandiri selalu menghasilkan warga.
        $user->role = UserRole::User;
        $user->save();

        return $user;
    }
}
