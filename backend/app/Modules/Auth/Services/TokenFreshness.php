<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Models\User;

/**
 * JWT bersifat stateless, jadi "cabut semua sesi" dilakukan dengan membandingkan
 * waktu terbit token (iat) dengan users.password_changed_at. Token yang terbit
 * sebelum password diganti dianggap basi.
 */
class TokenFreshness
{
    public function isStale(User $user, int $issuedAt): bool
    {
        if ($user->password_changed_at === null) {
            return false;
        }

        return $issuedAt < $user->password_changed_at->getTimestamp();
    }
}
