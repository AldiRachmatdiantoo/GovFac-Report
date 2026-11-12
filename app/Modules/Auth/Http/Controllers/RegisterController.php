<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;

class RegisterController
{
    public function __construct(private readonly AuthService $auth) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = $this->auth->register($request->safe()->only(['name', 'email', 'password']));

        return (new UserResource($user))->response()->setStatusCode(201);
    }
}
