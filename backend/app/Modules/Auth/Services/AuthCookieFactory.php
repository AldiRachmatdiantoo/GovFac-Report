<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Membuat dan menghapus dua cookie sesi:
 *
 * - access_token: JWT, httpOnly, tidak bisa dibaca JavaScript.
 * - XSRF-TOKEN:   string acak yang HARUS bisa dibaca frontend, lalu dikirim
 *                 balik lewat header X-XSRF-TOKEN (double-submit cookie).
 */
class AuthCookieFactory
{
    public const ACCESS_COOKIE = 'access_token';

    public const XSRF_COOKIE = 'XSRF-TOKEN';

    public const XSRF_HEADER = 'X-XSRF-TOKEN';

    /**
     * @return list<Cookie>
     */
    public function make(string $jwt): array
    {
        // Cookie hidup selama masa refresh, bukan selama masa access token:
        // endpoint refresh butuh token yang sudah kedaluwarsa tetap terkirim.
        $minutes = (int) config('jwt.refresh_ttl');

        return [
            $this->cookie(self::ACCESS_COOKIE, $jwt, $minutes, httpOnly: true),
            $this->cookie(self::XSRF_COOKIE, Str::random(40), $minutes, httpOnly: false),
        ];
    }

    /**
     * @return list<Cookie>
     */
    public function forget(): array
    {
        return [
            $this->cookie(self::ACCESS_COOKIE, '', -2628000, httpOnly: true),
            $this->cookie(self::XSRF_COOKIE, '', -2628000, httpOnly: false),
        ];
    }

    private function cookie(string $name, string $value, int $minutes, bool $httpOnly): Cookie
    {
        return cookie(
            name: $name,
            value: $value,
            minutes: $minutes,
            path: '/',
            domain: config('session.domain'),
            secure: ! app()->environment('local', 'testing'),
            httpOnly: $httpOnly,
            raw: false,
            sameSite: 'lax',
        );
    }
}
