<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Auth\Events\UserLoggedIn;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued, so a failure here can never break or slow down the login itself.
 */
class SendLoginNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(UserLoggedIn $event): void
    {
        $this->notifications->notifyLogin($event);
    }
}
