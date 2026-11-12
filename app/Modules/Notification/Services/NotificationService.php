<?php

namespace App\Modules\Notification\Services;

use App\Modules\Auth\Events\UserLoggedIn;
use App\Modules\Notification\Notifications\LoginDetected;
use App\Modules\Notification\Repositories\NotificationRepository;
use App\Modules\Shared\Support\Pagination;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationService
{
    public const PER_PAGE = Pagination::DEFAULT_PER_PAGE;

    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly AuthManager $auth,
    ) {}

    public function notifyLogin(UserLoggedIn $event): void
    {
        // We go through the auth user provider so this module never imports
        // the Auth module's User model.
        $user = $this->auth->createUserProvider('users')?->retrieveById($event->userId);

        if ($user === null) {
            return;
        }

        $user->notify(new LoginDetected($event->ip, $event->userAgent, $event->loggedInAt));
    }

    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function paginate(Model $user, bool $unreadOnly, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return $this->notifications->paginateFor($user, $unreadOnly, $perPage);
    }

    public function unreadCount(Model $user): int
    {
        return $this->notifications->unreadCountFor($user);
    }

    public function markAsRead(Model $user, string $id): DatabaseNotification
    {
        // Someone else's notification gets the same 404 as a missing one.
        $notification = $this->notifications->findFor($user, $id)
            ?? throw new NotFoundHttpException('Notifikasi tidak ditemukan.');

        $notification->markAsRead();

        return $notification;
    }

    public function markAllAsRead(Model $user): int
    {
        return $this->notifications->markAllAsReadFor($user);
    }
}
