<?php

namespace App\Modules\Auth\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_defaults_to_user_role(): void
    {
        $user = User::create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame(UserRole::User, $user->fresh()->role);
        $this->assertNull($user->fresh()->password_changed_at);
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->assertSame(UserRole::User, $user->fresh()->role);
    }

    public function test_factory_states_set_roles(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->hasRole(UserRole::Admin));

        $gov = User::factory()->government()->create();
        $this->assertTrue($gov->hasRole(UserRole::Government, UserRole::Admin));
        $this->assertNotNull($gov->position);
    }

    public function test_jwt_guard_issues_token_with_role_claim(): void
    {
        $user = User::factory()->admin()->create();

        $token = auth('api')->login($user);

        $this->assertIsString($token);
        $this->assertSame('admin', auth('api')->payload()->get('role'));
        $this->assertSame($user->id, (int) auth('api')->payload()->get('sub'));
    }
}
