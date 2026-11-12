<?php

namespace App\Modules\Auth\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dipicu oleh modul Auth setiap login berhasil.
 * Modul Notification mendengarkan event ini (PBI-3).
 */
class UserLoggedIn
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
        public readonly CarbonImmutable $loggedInAt,
    ) {}
}
