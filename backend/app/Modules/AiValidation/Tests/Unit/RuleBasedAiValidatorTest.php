<?php

namespace App\Modules\AiValidation\Tests\Unit;

use App\Modules\AiValidation\Contracts\AiVerdict;
use App\Modules\AiValidation\Services\RuleBasedAiValidator;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\Tag;
use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReasonCategory;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RuleBasedAiValidatorTest extends TestCase
{
    /**
     * @param  list<string>  $tags
     */
    private function report(string $title, string $description, array $tags = []): Report
    {
        $report = new Report(['title' => $title, 'description' => $description]);

        return $report->setRelation('tags', new Collection(array_map(fn (string $name) => new Tag(['name' => $name]), $tags)));
    }

    /**
     * @return array<string, array{string, string, list<string>, Criticality}>
     */
    public static function criticalityCases(): array
    {
        return [
            'danger word in the description' => ['Jalan rusak', 'Aspal ambruk dan membahayakan pengendara motor.', [], Criticality::High],
            'danger word only in a tag' => ['Saluran air', 'Saluran tersumbat di depan pasar sejak minggu lalu.', ['banjir'], Criticality::High],
            'cosmetic damage' => ['Halte', 'Dinding halte penuh coretan dan catnya mulai pudar.', [], Criticality::Low],
            'anything else' => ['Lampu jalan mati', 'Tiga lampu di depan taman mati sejak dua minggu.', ['penerangan'], Criticality::Medium],
        ];
    }

    /**
     * @param  list<string>  $tags
     */
    #[DataProvider('criticalityCases')]
    public function test_a_clear_report_passes_with_a_criticality_from_its_words(string $title, string $description, array $tags, Criticality $expected): void
    {
        $result = (new RuleBasedAiValidator)->validate($this->report($title, $description, $tags));

        $this->assertSame(AiVerdict::Pass, $result->verdict);
        $this->assertSame($expected, $result->criticality);
        $this->assertSame(RuleBasedAiValidator::MODEL, $result->model);
    }

    public function test_a_too_short_description_fails_as_incomplete(): void
    {
        $result = (new RuleBasedAiValidator)->validate($this->report('Lampu mati', '   Lampu mati.   '));

        $this->assertSame(AiVerdict::Fail, $result->verdict);
        $this->assertSame(ReasonCategory::Incomplete, $result->reasonCategory);
        $this->assertNotSame([], $result->reasons);
    }
}
