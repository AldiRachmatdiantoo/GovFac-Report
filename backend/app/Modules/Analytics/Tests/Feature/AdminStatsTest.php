<?php

namespace App\Modules\Analytics\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ReportStatusService $statuses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->statuses = app(ReportStatusService::class);
    }

    /**
     * Submit a new report at $at, the way CreateReportService records it.
     */
    private function submitted(string $at): Report
    {
        Carbon::setTestNow($at);
        $report = Report::factory()->create();
        $this->statuses->initialize($report, $report->reporter);
        Carbon::setTestNow();

        return $report;
    }

    /**
     * @param  list<array{ReportStatus, ?User, ?ReasonCategory}>  $steps
     */
    private function move(Report $report, string $at, array $steps): void
    {
        Carbon::setTestNow($at);

        foreach ($steps as [$to, $actor, $category]) {
            $this->statuses->transition($report, $to, $actor, $category === null ? null : 'Catatan penolakan.', $category);
        }

        Carbon::setTestNow();
    }

    private function seedNovember(): void
    {
        $approved = $this->submitted('2026-11-02 09:00:00');
        $this->move($approved, '2026-11-03 10:00:00', [
            [ReportStatus::AdminReview, null, null],
            [ReportStatus::Approved, $this->admin, null],
        ]);

        $rejectedTwice = $this->submitted('2026-11-04 09:00:00');
        $this->move($rejectedTwice, '2026-11-04 10:00:00', [
            [ReportStatus::AdminReview, null, null],
            [ReportStatus::Rejected, $this->admin, ReasonCategory::UnclearEvidence],
        ]);
        $this->move($rejectedTwice, '2026-11-06 10:00:00', [
            [ReportStatus::AiValidation, $rejectedTwice->reporter, null],
            [ReportStatus::AdminReview, null, null],
            [ReportStatus::Rejected, $this->admin, ReasonCategory::Duplicate],
        ]);

        $rejectedByAi = $this->submitted('2026-11-10 09:00:00');
        $this->move($rejectedByAi, '2026-11-10 09:01:00', [[ReportStatus::Rejected, null, ReasonCategory::Incomplete]]);

        // A rejection that carries no category still has to be counted somewhere.
        $uncategorised = $this->submitted('2026-11-12 09:00:00');
        $this->move($uncategorised, '2026-11-12 09:01:00', [[ReportStatus::Rejected, null, null]]);

        $this->submitted('2026-11-15 09:00:00');
    }

    public function test_totals_without_a_date_range(): void
    {
        $this->seedNovember();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertExactJson(['data' => [
                'from' => null,
                'to' => null,
                'submitted' => 5,
                'approved' => 1,
                'rejected' => 4,
                'rejected_by_reason' => [
                    'duplicate' => 1,
                    'unclear_evidence' => 1,
                    'not_public_facility' => 0,
                    'incomplete' => 1,
                    'other' => 1,
                ],
            ]]);
    }

    public function test_an_inclusive_date_range_limits_every_number(): void
    {
        $this->seedNovember();

        $data = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/stats?from=2026-11-03&to=2026-11-10')
            ->assertOk()
            ->json('data');

        $this->assertSame('2026-11-03', $data['from']);
        $this->assertSame('2026-11-10', $data['to']);
        $this->assertSame(2, $data['submitted']);
        $this->assertSame(1, $data['approved']);
        $this->assertSame(3, $data['rejected']);
        $this->assertSame(['duplicate' => 1, 'unclear_evidence' => 1, 'not_public_facility' => 0, 'incomplete' => 1, 'other' => 0], $data['rejected_by_reason']);
        $this->assertSame($data['rejected'], array_sum($data['rejected_by_reason']));
    }

    public function test_an_empty_range_returns_zeros(): void
    {
        $this->seedNovember();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/stats?from=2027-01-01')
            ->assertOk()
            ->assertJsonPath('data.submitted', 0)
            ->assertJsonPath('data.approved', 0)
            ->assertJsonPath('data.rejected', 0)
            ->assertJsonPath('data.rejected_by_reason', array_fill_keys(array_column(ReasonCategory::cases(), 'value'), 0));
    }

    public function test_dates_are_validated(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/stats?from=10/11/2026')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('from');

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/stats?from=2026-11-10&to=2026-11-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    }

    public function test_only_admins_see_the_stats(): void
    {
        $this->getJson('/api/v1/admin/stats')->assertUnauthorized();

        foreach ([User::factory()->create(), User::factory()->government()->create()] as $user) {
            $this->actingAs($user, 'api')->getJson('/api/v1/admin/stats')->assertForbidden();
        }
    }
}
