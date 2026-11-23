<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportPhoto;

class ReportRevisionRepository
{
    public function revise(Report $report, string $title, string $description): void
    {
        $report->fill(['title' => $title, 'description' => $description]);
        $report->revision++;
        $report->save();
    }

    /**
     * @return list<string> paths of the removed photos, for deleting the files after commit
     */
    public function removePhotos(Report $report): array
    {
        $photos = ReportPhoto::query()->where('report_id', $report->getKey());
        $paths = $photos->clone()->pluck('path')->all();
        $photos->delete();

        return $paths;
    }
}
