<?php

namespace App\Modules\Auth\Http\Resources;

use App\Modules\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profil akun untuk pemiliknya sendiri. Jangan dipakai untuk menampilkan
 * user lain (memuat email); modul lain cukup menampilkan id dan name.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'position' => $this->position,
            'agency' => $this->agency,
            'created_at' => $this->created_at,
        ];
    }
}
