<?php

namespace App\Modules\Notification\Tests\Unit;

use App\Modules\Notification\Services\DeviceLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeviceLabelTest extends TestCase
{
    /**
     * @return array<string, array{?string, string}>
     */
    public static function userAgents(): array
    {
        return [
            'chrome windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36', 'Chrome di Windows'],
            'chrome android' => ['Mozilla/5.0 (Linux; Android 14; SM-A546E) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', 'Chrome di Android'],
            'safari iphone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1', 'Safari di iPhone'],
            'edge windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0', 'Edge di Windows'],
            'firefox linux' => ['Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0', 'Firefox di Linux'],
            'curl' => ['curl/8.9.1', 'curl/8.9.1'],
            'postman' => ['PostmanRuntime/7.42.0', 'PostmanRuntime/7.42.0'],
            'empty' => ['', 'perangkat tidak dikenal'],
            'null' => [null, 'perangkat tidak dikenal'],
        ];
    }

    #[DataProvider('userAgents')]
    public function test_it_describes_the_device(?string $userAgent, string $expected): void
    {
        $this->assertSame($expected, DeviceLabel::fromUserAgent($userAgent));
    }
}
