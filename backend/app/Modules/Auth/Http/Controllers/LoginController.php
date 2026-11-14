<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthCookieFactory;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;

class LoginController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly AuthCookieFactory $cookies,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $session = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->ip(),
            $request->userAgent(),
        );

        // Token hanya dikirim lewat cookie httpOnly, tidak pernah di body.
        $response = response()->json(['data' => ['user' => new UserResource($session['user'])]]);

        foreach ($this->cookies->make($session['token']) as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }
}
