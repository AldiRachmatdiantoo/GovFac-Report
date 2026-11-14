<?php

namespace App\Modules\Notification\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class NotificationRepository
{
    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function paginateFor(Model $notifiable, bool $unreadOnly, int $perPage): LengthAwarePaginator
    {
        return $this->queryFor($notifiable)
            ->when($unreadOnly, fn (Builder $query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate($perPage);
    }

    public function unreadCountFor(Model $notifiable): int
    {
        return $this->queryFor($notifiable)->whereNull('read_at')->count();
    }

    public function findFor(Model $notifiable, string $id): ?DatabaseNotification
    {
        return $this->queryFor($notifiable)->find($id);
    }

    public function markAllAsReadFor(Model $notifiable): int
    {
        return $this->queryFor($notifiable)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * @return Builder<DatabaseNotification>
     */
    private function queryFor(Model $notifiable): Builder
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey());
    }
}
