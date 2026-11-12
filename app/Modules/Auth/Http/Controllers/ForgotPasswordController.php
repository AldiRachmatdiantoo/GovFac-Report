<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController
{
    public function __construct(private readonly PasswordResetService $passwordReset) {}

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordReset->sendLink($request->validated('email'));

        return response()->json([
            'message' => 'Jika email terdaftar, tautan untuk mengatur ulang password sudah dikirim.',
        ]);
    }
}
