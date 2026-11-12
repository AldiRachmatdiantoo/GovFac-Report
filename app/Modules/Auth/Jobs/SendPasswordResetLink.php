<?php

namespace App\Modules\Auth\Jobs;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Notifications\ResetPasswordLink;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLink implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $email) {}

    public function handle(): void
    {
        // Unknown emails and the broker's own per-user throttle both end here
        // silently; the HTTP response was already identical for everyone.
        Password::sendResetLink(['email' => $this->email], function (User $user, string $token): void {
            $user->notify(new ResetPasswordLink($token));
        });
    }
}
