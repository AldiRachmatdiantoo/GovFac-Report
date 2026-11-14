<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggestions_match_prefix_rank_usage_and_limit_to_ten(): void
    {
        $user = User::factory()->create();
        $popular = Tag::create(['name' => 'jalan_rusak']);
        $popular->reports()->attach(Report::factory()->count(3)->create()->modelKeys());
        foreach (range(1, 12) as $number) {
            Tag::create(['name' => 'jalan'.$number]);
        }
        Tag::create(['name' => 'bukanjalan']);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/tags?q=%23JALAN')
            ->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('data.0.name', 'jalan_rusak');
        foreach ($response->json('data') as $tag) {
            $this->assertStringStartsWith('jalan', $tag['name']);
        }
    }

    public function test_underscore_is_a_literal_prefix_character(): void
    {
        Tag::create(['name' => 'jalan_rusak']);
        Tag::create(['name' => 'jalanbesar']);
        $this->actingAs(User::factory()->create(), 'api')->getJson('/api/v1/tags?q=jalan_')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'jalan_rusak');
    }

    public function test_all_authenticated_roles_can_get_suggestions(): void
    {
        foreach ([User::factory()->create(), User::factory()->admin()->create(), User::factory()->government()->create()] as $user) {
            $this->actingAs($user, 'api')->getJson('/api/v1/tags')->assertOk();
        }
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->getJson('/api/v1/tags')->assertUnauthorized();
    }

    public function test_invalid_query_is_rejected_and_unknown_prefix_is_empty(): void
    {
        $this->actingAs(User::factory()->create(), 'api')->getJson('/api/v1/tags?q[]=jalan')
            ->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson('/api/v1/tags?q=unknown')->assertOk()->assertExactJson(['data' => []]);
    }
}
