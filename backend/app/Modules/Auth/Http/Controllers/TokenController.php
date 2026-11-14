<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthCookieFactory;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class TokenController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly AuthCookieFactory $cookies,
    ) {}

    public function refresh(): JsonResponse
    {
        try {
            $session = $this->auth->refresh();
        } catch (AuthenticationException $e) {
            // Sesi tidak bisa diperpanjang: bersihkan cookie agar browser tidak
            // terus mengirim token yang sudah mati.
            $response = response()->json(['message' => $e->getMessage()], 401);

            foreach ($this->cookies->forget() as $cookie) {
                $response->withCookie($cookie);
            }

            return $response;
        }

        $response = response()->json(['data' => ['user' => new UserResource($session['user'])]]);

        foreach ($this->cookies->make($session['token']) as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        $response = response()->json(['message' => 'Berhasil logout.']);

        foreach ($this->cookies->forget() as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }
}
