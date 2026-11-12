<?php

namespace App\Modules\Report\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Events\ReportStatusChanged;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Repositories\ReportReadRepository;
use App\Modules\Report\Repositories\ReportStatusHistoryRepository;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Exceptions\InvalidStatusTransition;
use Illuminate\Support\Facades\DB;

class EloquentReportStatusService implements ReportStatusService
{
    public function __construct(
        private readonly ReportReadRepository $reports,
        private readonly ReportStatusHistoryRepository $histories,
    ) {}

    public function initialize(Report $report, ?User $actor = null): void
    {
        $this->histories->append(
            report: $report,
            from: null,
            to: ReportStatus::AiValidation,
            actorId: $actor?->getKey(),
        );
    }

    public function transition(
        Report $report,
        ReportStatus $to,
        ?User $actor = null,
        ?string $note = null,
        ?ReasonCategory $reasonCategory = null,
    ): Report {
        $from = null;

        $updated = DB::transaction(function () use ($report, $to, $actor, $note, $reasonCategory, &$from) {
            // Status dibaca ulang dari baris yang terkunci, bukan dari model yang
            // dioper pemanggil, karena model itu bisa saja sudah basi.
            $locked = $this->reports->lockById($report->getKey());
            $from = $locked->status;

            if (! ReportStatusTransitions::canTransition($from, $to)) {
                throw new InvalidStatusTransition($from, $to);
            }

            $locked->status = $to;
            $locked->save();

            $this->histories->append(
                report: $locked,
                from: $from,
                to: $to,
                actorId: $actor?->getKey(),
                note: $note,
                reasonCategory: $reasonCategory,
            );

            return $locked;
        });

        // Sinkronkan instance milik pemanggil agar ikut menampilkan status terbaru.
        $report->setRawAttributes($updated->getAttributes(), true);

        // afterCommit: bila dipanggil dari dalam transaksi pemanggil, event baru
        // terkirim setelah transaksi terluar benar-benar commit.
        DB::afterCommit(fn () => ReportStatusChanged::dispatch(
            $updated,
            $from,
            $to,
            $actor,
            $note,
            $reasonCategory,
        ));

        return $updated;
    }
}
