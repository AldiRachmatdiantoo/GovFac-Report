<?php

namespace App\Modules\Report\Tests\Unit;

use App\Modules\Report\Services\ReportCodeGenerator;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReportCodeGeneratorTest extends TestCase
{
    public function test_codes_use_persisted_id_and_creation_year(): void
    {
        $generator = new ReportCodeGenerator;
        $codes = [];
        foreach ([1, 2, 999999] as $id) {
            $codes[] = $generator->generate($id, new DateTimeImmutable('2026-11-12'));
        }
        $this->assertSame(['GFR-2026-000001', 'GFR-2026-000002', 'GFR-2026-999999'], $codes);
        $this->assertCount(3, array_unique($codes));
    }

    public function test_unpersisted_reports_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ReportCodeGenerator)->generate(0, new DateTimeImmutable('2026-11-12'));
    }
}
