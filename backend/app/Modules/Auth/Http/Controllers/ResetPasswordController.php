<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use App\Modules\Auth\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

class ResetPasswordController
{
    public function __construct(private readonly PasswordResetService $passwordReset) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordReset->reset($request->safe()->only(['token', 'email', 'password']));

        return response()->json([
            'message' => 'Password berhasil diubah. Silakan login kembali.',
        ]);
    }
}
