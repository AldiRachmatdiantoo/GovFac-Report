<?php

namespace App\Modules\Report\Contracts;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Exceptions\InvalidStatusTransition;

/**
 * Satu-satunya pintu perubahan status laporan di seluruh aplikasi.
 *
 * Modul lain (Verification, Government, AiValidation) memanggil interface ini;
 * tidak ada yang mengubah `reports.status` secara langsung.
 *
 * Interface ini hanya memeriksa keabsahan transisi STATUS. Siapa yang boleh
 * memicunya (peran, kepemilikan) diperiksa oleh Policy modul pemanggil.
 */
interface ReportStatusService
{
    /**
     * Catat riwayat awal laporan baru (null -> ai_validation).
     */
    public function initialize(Report $report, ?User $actor = null): void;

    /**
     * Pindahkan status dan tulis satu baris riwayat dalam satu transaksi.
     *
     * @param  User|null  $actor  null berarti pelakunya sistem.
     *
     * @throws InvalidStatusTransition bila transisi tidak sah.
     */
    public function transition(
        Report $report,
        ReportStatus $to,
        ?User $actor = null,
        ?string $note = null,
        ?ReasonCategory $reasonCategory = null,
    ): Report;
}
