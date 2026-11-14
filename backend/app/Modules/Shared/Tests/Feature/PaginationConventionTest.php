<?php

namespace App\Modules\Shared\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Notification\Notifications\LoginDetected;
use App\Modules\Report\Models\Report;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Semua endpoint daftar harus mengikuti konvensi yang sama:
 * bawaan 20 per halaman, `per_page` boleh diatur, maksimal 50.
 */
class PaginationConventionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function listEndpoints(): array
    {
        return [
            'laporan saya' => ['/api/v1/reports/mine'],
            'notifikasi' => ['/api/v1/notifications'],
        ];
    }

    private function userWithTwentyTwoItems(): User
    {
        $user = User::factory()->create();

        Report::factory()->count(22)->create(['reporter_id' => $user->id]);

        for ($i = 0; $i < 22; $i++) {
            $user->notify(new LoginDetected("10.0.0.{$i}", 'PHPUnit', CarbonImmutable::now()));
        }

        return $user;
    }

    #[DataProvider('listEndpoints')]
    public function test_lists_default_to_twenty_per_page(string $uri): void
    {
        $this->actingAs($this->userWithTwentyTwoItems(), 'api')
            ->getJson($uri)
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 22);
    }

    #[DataProvider('listEndpoints')]
    public function test_lists_honour_per_page(string $uri): void
    {
        $this->actingAs($this->userWithTwentyTwoItems(), 'api')
            ->getJson($uri.'?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.last_page', 5);
    }

    #[DataProvider('listEndpoints')]
    public function test_lists_reject_per_page_outside_one_to_fifty(string $uri): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->getJson($uri.'?per_page=51')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->actingAs($user, 'api')->getJson($uri.'?per_page=0')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->actingAs($user, 'api')->getJson($uri.'?per_page=50')->assertOk();
    }
}
