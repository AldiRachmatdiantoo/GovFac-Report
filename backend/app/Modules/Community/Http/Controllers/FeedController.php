<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Http\Requests\FeedRequest;
use App\Modules\Community\Http\Resources\FeedReportResource;
use App\Modules\Community\Services\FeedService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    public function __construct(private readonly FeedService $feed) {}

    /**
     * GET /api/v1/feed
     */
    public function __invoke(FeedRequest $request): AnonymousResourceCollection
    {
        return FeedReportResource::collection($this->feed->paginate($request->user(), $request->filter()));
    }
}
