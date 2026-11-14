<?php

namespace App\Modules\Notification\Http\Controllers;

use App\Modules\Notification\Http\Requests\ListNotificationsRequest;
use App\Modules\Notification\Http\Resources\NotificationResource;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NotificationController
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(ListNotificationsRequest $request): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            $this->notifications->paginate($request->user(), $request->boolean('unread'), $request->perPage()),
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ['count' => $this->notifications->unreadCount($request->user())],
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->notifications->markAsRead($request->user(), $id);

        // The resource has its own `data` key, which makes Laravel skip the usual
        // `data` wrapper, so we add it ourselves to keep responses consistent.
        return response()->json(['data' => new NotificationResource($notification)]);
    }

    public function markAllAsRead(Request $request): Response
    {
        $this->notifications->markAllAsRead($request->user());

        return response()->noContent();
    }
}
