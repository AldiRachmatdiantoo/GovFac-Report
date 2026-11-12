<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Services\AuthCookieFactory;
use App\Modules\Auth\Services\TokenFreshness;
use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Response;

/**
 * Berjalan paling awal di grup middleware "api".
 *
 * 1. Menyalin JWT dari cookie httpOnly ke header Authorization, sehingga guard
 *    `auth:api` bekerja tanpa frontend pernah memegang token.
 * 2. Membuang token yang terbit sebelum password diganti, sehingga route
 *    terproteksi menjawab 401 dan route publik (mis. login) tetap bisa dipakai.
 */
class AuthenticateFromCookie
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly TokenFreshness $freshness,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $cookie = $request->cookie(AuthCookieFactory::ACCESS_COOKIE);

        if (is_string($cookie) && $cookie !== '' && ! $request->headers->has('Authorization')) {
            $request->headers->set('Authorization', 'Bearer '.$cookie);
        }

        if ($request->headers->has('Authorization')) {
            $this->dropStaleToken($request);
        }

        return $next($request);
    }

    private function dropStaleToken(Request $request): void
    {
        /** @var JWTGuard $guard */
        $guard = $this->auth->guard('api');

        try {
            $user = $guard->user();

            if (! $user instanceof User) {
                // Token tidak sah atau kedaluwarsa: biarkan `auth:api` yang menolak,
                // dan biarkan endpoint refresh mencoba menukarnya.
                return;
            }

            if (! $this->freshness->isStale($user, (int) $guard->payload()->get('iat'))) {
                return;
            }
        } catch (JWTException) {
            return;
        }

        $request->headers->remove('Authorization');
        $guard->unsetToken();
        $guard->forgetUser();
    }
}
