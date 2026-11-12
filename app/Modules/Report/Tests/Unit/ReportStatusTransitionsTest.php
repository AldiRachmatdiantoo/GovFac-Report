<?php

namespace App\Modules\Report\Tests\Unit;

use App\Modules\Report\Services\ReportStatusTransitions;
use App\Modules\Shared\Enums\ReportStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReportStatusTransitionsTest extends TestCase
{
    /**
     * Transisi sah menurut tabel PRD (12 pasangan). Selain ini, ditolak.
     *
     * @var array<string, list<ReportStatus>>
     */
    private const VALID = [
        'ai_validation' => [ReportStatus::AdminReview, ReportStatus::Rejected, ReportStatus::Cancelled],
        'admin_review' => [ReportStatus::Approved, ReportStatus::Rejected, ReportStatus::Cancelled],
        'rejected' => [ReportStatus::AiValidation],
        'approved' => [ReportStatus::Forwarded, ReportStatus::Cancelled],
        'forwarded' => [ReportStatus::GovApproved, ReportStatus::Cancelled],
        'gov_approved' => [ReportStatus::Scheduled],
        'scheduled' => [],
        'cancelled' => [],
    ];

    /**
     * Seluruh matriks 8 x 8 = 64 pasangan, masing-masing dengan hasil yang diharapkan.
     *
     * @return array<string, array{0: ReportStatus, 1: ReportStatus, 2: bool}>
     */
    public static function matrix(): array
    {
        $cases = [];

        foreach (ReportStatus::cases() as $from) {
            foreach (ReportStatus::cases() as $to) {
                $expected = in_array($to, self::VALID[$from->value], true);
                $cases["{$from->value} -> {$to->value}"] = [$from, $to, $expected];
            }
        }

        return $cases;
    }

    #[DataProvider('matrix')]
    public function test_matrix(ReportStatus $from, ReportStatus $to, bool $expected): void
    {
        $this->assertSame($expected, ReportStatusTransitions::canTransition($from, $to));
    }

    public function test_matrix_covers_64_pairs_with_12_valid(): void
    {
        $cases = self::matrix();

        $this->assertCount(64, $cases);
        $this->assertCount(12, array_filter($cases, fn (array $case) => $case[2]));
    }

    public function test_next_states_agrees_with_can_transition(): void
    {
        foreach (ReportStatus::cases() as $from) {
            foreach (ReportStatus::cases() as $to) {
                $this->assertSame(
                    ReportStatusTransitions::canTransition($from, $to),
                    in_array($to, ReportStatusTransitions::nextStates($from), true),
                    "nextStates dan canTransition tidak konsisten untuk {$from->value} -> {$to->value}",
                );
            }
        }
    }
}
