<?php

namespace App\Modules\Community\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Community\Repositories\ReportLikeRepository;
use App\Modules\Report\Contracts\ReportFeed;
use App\Modules\Report\Contracts\ReportLikeCounter;
use App\Modules\Report\Models\Report;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ReportLikeService
{
    public function __construct(
        private readonly ReportFeed $feed,
        private readonly ReportLikeRepository $likes,
        private readonly ReportLikeCounter $counter,
    ) {}

    /**
     * Idempotent: liking twice keeps one like and one count.
     *
     * @return array{report_id: int, liked: bool, likes_count: int}
     *
     * @throws ModelNotFoundException when the report is not in the feed
     */
    public function like(User $user, int $reportId): array
    {
        $this->findInFeedOrFail($reportId);

        $likesCount = DB::transaction(fn (): int => $this->likes->add($reportId, $user->getKey())
            ? $this->counter->increment($reportId)
            : $this->counter->count($reportId));

        return ['report_id' => $reportId, 'liked' => true, 'likes_count' => $likesCount];
    }

    /**
     * Idempotent: removing a like that does not exist changes nothing.
     *
     * @return array{report_id: int, liked: bool, likes_count: int}
     *
     * @throws ModelNotFoundException when the report is not in the feed
     */
    public function unlike(User $user, int $reportId): array
    {
        $this->findInFeedOrFail($reportId);

        $likesCount = DB::transaction(fn (): int => $this->likes->remove($reportId, $user->getKey())
            ? $this->counter->decrement($reportId)
            : $this->counter->count($reportId));

        return ['report_id' => $reportId, 'liked' => false, 'likes_count' => $likesCount];
    }

    private function findInFeedOrFail(int $reportId): Report
    {
        return $this->feed->findVisible($reportId)
            ?? throw (new ModelNotFoundException)->setModel(Report::class, [$reportId]);
    }
}
