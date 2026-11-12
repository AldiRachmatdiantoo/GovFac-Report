<?php

namespace App\Modules\Report\Http\Resources;

use App\Modules\Report\Models\ReportStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReportStatusHistory
 */
class ReportStatusHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'from_status' => $this->from_status?->value ?? (is_string($this->from_status) ? $this->from_status : null),
            'to_status' => $this->to_status instanceof \BackedEnum ? $this->to_status->value : $this->to_status,
            'actor' => $this->actor ? [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'role' => $this->actor->role instanceof \BackedEnum ? $this->actor->role->value : $this->actor->role,
            ] : null,
            'reason_category' => $this->reason_category?->value ?? (is_string($this->reason_category) ? $this->reason_category : null),
            'note' => $this->note,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
