<?php

namespace App\Modules\Notification\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Notification\Notifications\LoginDetected;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function notifyLogin(User $user, string $ip): void
    {
        $user->notify(new LoginDetected($ip, 'curl/8.9.1', CarbonImmutable::now()));
    }

    public function test_guest_gets_401_on_every_endpoint(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $this->getJson('/api/v1/notifications/unread-count')->assertUnauthorized();
        $this->patchJson('/api/v1/notifications/read-all')->assertUnauthorized();
        $this->patchJson('/api/v1/notifications/'.Str::uuid().'/read')->assertUnauthorized();
    }

    public function test_index_lists_only_own_notifications_newest_first(): void
    {
        $user = User::factory()->create();
        $this->notifyLogin($user, '10.0.0.1');
        $this->travel(1)->minutes();
        $this->notifyLogin($user, '10.0.0.2');
        $this->notifyLogin(User::factory()->create(), '10.9.9.9');

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.data.ip', '10.0.0.2')
            ->assertJsonPath('data.1.data.ip', '10.0.0.1')
            ->assertJsonPath('data.0.type', 'login')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_index_is_paginated_by_20(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 21; $i++) {
            $this->notifyLogin($user, "10.0.0.{$i}");
        }

        $this->actingAs($user, 'api')->getJson('/api/v1/notifications')->assertJsonCount(20, 'data');
        $this->actingAs($user, 'api')->getJson('/api/v1/notifications?page=2')->assertJsonCount(1, 'data');
    }

    public function test_unread_filter(): void
    {
        $user = User::factory()->create();
        $this->notifyLogin($user, '10.0.0.1');
        $this->notifyLogin($user, '10.0.0.2');
        $user->notifications()->where('data->ip', '10.0.0.1')->first()->markAsRead();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.ip', '10.0.0.2');

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications?unread=maybe')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('unread');
    }

    public function test_unread_count_drops_after_marking_one_as_read(): void
    {
        $user = User::factory()->create();
        $this->notifyLogin($user, '10.0.0.1');
        $this->notifyLogin($user, '10.0.0.2');
        $id = $user->notifications()->first()->id;

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertExactJson(['data' => ['count' => 2]]);

        $this->actingAs($user, 'api')
            ->patchJson("/api/v1/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.read_at', fn ($readAt) => $readAt !== null);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertExactJson(['data' => ['count' => 1]]);
    }

    public function test_marking_someone_elses_notification_returns_404(): void
    {
        $owner = User::factory()->create();
        $this->notifyLogin($owner, '10.0.0.1');
        $id = $owner->notifications()->first()->id;

        $this->actingAs(User::factory()->create(), 'api')
            ->patchJson("/api/v1/notifications/{$id}/read")
            ->assertNotFound()
            ->assertJsonPath('message', 'Notifikasi tidak ditemukan.');

        $this->assertNull($owner->notifications()->first()->read_at);
    }

    public function test_unknown_or_malformed_id_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->patchJson('/api/v1/notifications/'.Str::uuid().'/read')->assertNotFound();
        $this->actingAs($user, 'api')->patchJson('/api/v1/notifications/not-a-uuid/read')->assertNotFound();
    }

    public function test_read_all_only_touches_own_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->notifyLogin($user, '10.0.0.1');
        $this->notifyLogin($user, '10.0.0.2');
        $this->notifyLogin($other, '10.9.9.9');

        $this->actingAs($user, 'api')
            ->patchJson('/api/v1/notifications/read-all')
            ->assertNoContent();

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }
}
