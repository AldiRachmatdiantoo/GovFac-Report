<?php

namespace App\Modules\Shared\Exceptions;

use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Dilempar ketika sebuah aksi tidak sah untuk status laporan saat ini.
 * Dirender sebagai 409 sesuai API contract.
 */
class InvalidStatusTransition extends RuntimeException
{
    public function __construct(
        public readonly ReportStatus $current,
        public readonly ?ReportStatus $target = null,
        ?string $message = null,
    ) {
        parent::__construct($message ?? sprintf(
            'Aksi ini tidak bisa dilakukan pada laporan berstatus %s.',
            $current->value,
        ));
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'invalid_status_transition',
            'current_status' => $this->current->value,
        ], 409);
    }
}
