<?php

namespace App\Modules\Notification\Tests\Feature;

use App\Modules\Auth\Events\UserLoggedIn;
use App\Modules\Auth\Models\User;
use App\Modules\Notification\Listeners\SendLoginNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LoginNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        // The listener is queued; we run it inline so the test sees its effect
        // no matter which queue connection the environment points at.
        config(['queue.default' => 'sync']);
    }

    public function test_listener_is_registered_for_the_login_event(): void
    {
        Event::fake();

        Event::assertListening(UserLoggedIn::class, SendLoginNotification::class);
    }

    public function test_user_logged_in_event_creates_exactly_one_login_notification(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $at = CarbonImmutable::parse('2026-11-12 09:30:00');

        UserLoggedIn::dispatch($user->id, '10.0.0.5', self::CHROME_WINDOWS, $at);

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame(0, $other->notifications()->count());

        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->first();
        $this->assertSame('login', $notification->type);
        $this->assertNull($notification->read_at);
        $this->assertSame('Login berhasil', $notification->data['title']);
        $this->assertStringContainsString('Chrome di Windows', $notification->data['message']);
        $this->assertStringContainsString('10.0.0.5', $notification->data['message']);
        $this->assertSame('10.0.0.5', $notification->data['ip']);
        $this->assertSame(self::CHROME_WINDOWS, $notification->data['user_agent']);
        $this->assertSame($at->toIso8601String(), $notification->data['logged_in_at']);
    }

    public function test_event_for_unknown_user_is_ignored(): void
    {
        UserLoggedIn::dispatch(999_999, '10.0.0.5', self::CHROME_WINDOWS, CarbonImmutable::now());

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_missing_ip_and_user_agent_still_produce_a_readable_message(): void
    {
        $user = User::factory()->create();

        UserLoggedIn::dispatch($user->id, null, null, CarbonImmutable::now());

        $message = $user->notifications()->first()->data['message'];
        $this->assertStringContainsString('perangkat tidak dikenal', $message);
        $this->assertStringContainsString('IP tidak diketahui', $message);
    }
}
