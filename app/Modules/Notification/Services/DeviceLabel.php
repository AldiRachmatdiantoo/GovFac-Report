<?php

namespace App\Modules\Notification\Services;

use Illuminate\Support\Str;

/**
 * We only need something a person recognises ("Chrome di Windows"), not a full
 * user-agent parser.
 */
final class DeviceLabel
{
    // Order matters: Edge and Opera UAs also contain "Chrome", Chrome's contains
    // "Safari", iOS UAs contain "Mac OS X" and Android UAs contain "Linux".
    private const BROWSERS = [
        'Edg/' => 'Edge',
        'OPR/' => 'Opera',
        'SamsungBrowser/' => 'Samsung Internet',
        'Firefox/' => 'Firefox',
        'Chrome/' => 'Chrome',
        'Safari/' => 'Safari',
    ];

    private const SYSTEMS = [
        'Windows' => 'Windows',
        'Android' => 'Android',
        'iPhone' => 'iPhone',
        'iPad' => 'iPad',
        'Mac OS X' => 'macOS',
        'Linux' => 'Linux',
    ];

    public static function fromUserAgent(?string $userAgent): string
    {
        $userAgent = trim((string) $userAgent);
        $browser = self::firstMatch($userAgent, self::BROWSERS);
        $system = self::firstMatch($userAgent, self::SYSTEMS);

        return match (true) {
            $browser !== null && $system !== null => "{$browser} di {$system}",
            $browser !== null => $browser,
            $system !== null => $system,
            $userAgent === '' => 'perangkat tidak dikenal',
            default => Str::limit(Str::before($userAgent, ' '), 40, ''),
        };
    }

    /**
     * @param  array<string, string>  $candidates
     */
    private static function firstMatch(string $userAgent, array $candidates): ?string
    {
        foreach ($candidates as $token => $name) {
            if (str_contains($userAgent, $token)) {
                return $name;
            }
        }

        return null;
    }
}
