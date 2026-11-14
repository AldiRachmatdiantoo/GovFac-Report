<?php

namespace App\Modules\Shared\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Exceptions\InvalidStatusTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'auth:api', 'role:admin,government'])
            ->get('/api/v1/_test/staff', fn () => response()->json(['data' => 'ok']));

        Route::middleware('api')->get('/api/v1/_test/conflict', function () {
            throw new InvalidStatusTransition(ReportStatus::GovApproved);
        });
    }

    public function test_guest_gets_401_json(): void
    {
        $this->getJson('/api/v1/_test/staff')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_wrong_role_gets_403(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->getJson('/api/v1/_test/staff')
            ->assertForbidden();
    }

    public function test_allowed_roles_pass(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'api')
            ->getJson('/api/v1/_test/staff')
            ->assertOk();

        $this->actingAs(User::factory()->government()->create(), 'api')
            ->getJson('/api/v1/_test/staff')
            ->assertOk();
    }

    public function test_invalid_status_transition_renders_409(): void
    {
        $this->getJson('/api/v1/_test/conflict')
            ->assertStatus(409)
            ->assertJson([
                'code' => 'invalid_status_transition',
                'current_status' => 'gov_approved',
            ]);
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->get('/api/v1/_test/missing')
            ->assertNotFound()
            ->assertHeader('content-type', 'application/json');
    }
}
