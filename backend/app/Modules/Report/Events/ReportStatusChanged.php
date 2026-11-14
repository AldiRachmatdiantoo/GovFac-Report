<?php

namespace App\Modules\Report\Events;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Report $report,
        public readonly ?ReportStatus $fromStatus,
        public readonly ReportStatus $toStatus,
        public readonly ?User $actor = null,
        public readonly ?string $note = null,
        public readonly ?ReasonCategory $reasonCategory = null,
    ) {}
}
