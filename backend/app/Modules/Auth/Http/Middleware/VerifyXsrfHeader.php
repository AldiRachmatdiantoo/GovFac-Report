<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Services\AuthCookieFactory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Perlindungan CSRF untuk sesi berbasis cookie (double-submit cookie).
 *
 * Browser mengirim cookie secara otomatis, termasuk dari situs lain. Karena itu
 * setiap permintaan yang mengubah data dan membawa cookie sesi wajib menyertakan
 * header X-XSRF-TOKEN yang nilainya sama dengan cookie XSRF-TOKEN. Situs lain
 * tidak bisa membaca cookie itu, jadi tidak bisa mengisi header-nya.
 *
 * Permintaan tanpa cookie sesi (login pertama, klien non-browser) dilewatkan.
 */
class VerifyXsrfHeader
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        if (! $request->cookies->has(AuthCookieFactory::ACCESS_COOKIE)) {
            return $next($request);
        }

        $cookie = (string) $request->cookie(AuthCookieFactory::XSRF_COOKIE);
        $header = (string) $request->header(AuthCookieFactory::XSRF_HEADER);

        abort_if(
            $cookie === '' || ! hash_equals($cookie, $header),
            419,
            'Token CSRF tidak cocok. Muat ulang halaman lalu coba lagi.',
        );

        return $next($request);
    }
}
