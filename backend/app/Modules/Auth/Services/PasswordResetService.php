<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Jobs\SendPasswordResetLink;
use App\Modules\Auth\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function sendLink(string $email): void
    {
        // For a registered email the broker also hashes and stores a token,
        // which is measurably slower. Doing it on the queue keeps the response
        // time the same, so it can't be used to probe which emails exist.
        SendPasswordResetLink::dispatch($email);
    }

    /**
     * @param  array{token: string, email: string, password: string}  $data
     */
    public function reset(array $data): void
    {
        $status = Password::reset($data, function (User $user, string $password): void {
            // password_changed_at is what makes Dev A's guard reject every JWT
            // issued before this moment.
            $user->forceFill([
                'password' => $password,
                'password_changed_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Tautan reset password tidak valid atau sudah kedaluwarsa.',
            ]);
        }
    }
}
