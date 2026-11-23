<?php

namespace App\Modules\Verification\Http\Resources;

use App\Modules\Report\Models\ReportStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One admin decision, with the report as it is now.
 *
 * @mixin ReportStatusHistory
 */
class AdminDecisionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $report = $this->report;

        return [
            'id' => $this->id,
            'decision' => $this->to_status->value,
            'reason_category' => $this->reason_category?->value,
            'note' => $this->note,
            'decided_at' => $this->created_at,
            'decided_by' => $this->actor === null ? null : ['id' => $this->actor->id, 'name' => $this->actor->name],
            'report' => [
                'id' => $report->id,
                'code' => $report->code,
                'title' => $report->title,
                'status' => $report->status->value,
                'criticality' => $report->criticality?->value,
                'revision' => $report->revision,
                'likes_count' => $report->likes_count,
                'reporter' => ['id' => $report->reporter->id, 'name' => $report->reporter->name],
            ],
        ];
    }
}
