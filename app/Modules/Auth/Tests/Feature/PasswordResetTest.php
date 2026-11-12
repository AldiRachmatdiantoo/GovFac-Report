<?php

namespace App\Modules\Auth\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Notifications\ResetPasswordLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const SENT_MESSAGE = 'Jika email terdaftar, tautan untuk mengatur ulang password sudah dikirim.';

    protected function setUp(): void
    {
        parent::setUp();

        // The link is sent from a queued job; we run it inline so the test sees
        // its effect no matter which queue connection the environment points at.
        config(['queue.default' => 'sync']);
    }

    private function resetPayload(string $token, string $email, string $password = 'passwordbaru1'): array
    {
        return [
            'token' => $token,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }

    public function test_forgot_password_sends_a_frontend_link_to_a_registered_email(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://govfac.test']);
        $user = User::factory()->create(['email' => 'budi@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'budi@example.com'])
            ->assertOk()
            ->assertExactJson(['message' => self::SENT_MESSAGE]);

        Notification::assertSentTo($user, ResetPasswordLink::class, function (ResetPasswordLink $notification) use ($user) {
            $url = $notification->url($user);

            return str_starts_with($url, 'https://govfac.test/reset-password?')
                && str_contains($url, 'token='.$notification->token)
                && str_contains($url, 'email=budi%40example.com')
                && Password::tokenExists($user, $notification->token);
        });
    }

    public function test_forgot_password_answers_the_same_for_an_unknown_email_and_sends_nothing(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'budi@example.com']);

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'budi@example.com']);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'siapa@example.com']);

        $unknown->assertOk();
        $this->assertSame($known->getContent(), $unknown->getContent());
        Notification::assertSentTimes(ResetPasswordLink::class, 1);
    }

    public function test_forgot_password_validates_the_email(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'bukan-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_forgot_password_is_throttled_after_five_requests_a_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => 'siapa@example.com'])->assertOk();
        }

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'siapa@example.com'])->assertTooManyRequests();
    }

    public function test_reset_with_a_valid_token_changes_the_password_and_sets_password_changed_at(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', $this->resetPayload($token, 'budi@example.com'))
            ->assertOk()
            ->assertJsonPath('message', 'Password berhasil diubah. Silakan login kembali.');

        $user->refresh();
        $this->assertTrue(Hash::check('passwordbaru1', $user->password));
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue($user->password_changed_at->isToday());
    }

    public function test_the_same_token_cannot_be_used_twice(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', $this->resetPayload($token, 'budi@example.com'))->assertOk();

        $this->postJson('/api/v1/auth/reset-password', $this->resetPayload($token, 'budi@example.com', 'passwordlain1'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertTrue(Hash::check('passwordbaru1', $user->fresh()->password));
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com']);
        $token = Password::createToken($user);

        $this->travel(31)->minutes();

        $this->postJson('/api/v1/auth/reset-password', $this->resetPayload($token, 'budi@example.com'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertNull($user->fresh()->password_changed_at);
    }

    public function test_a_wrong_token_or_another_users_email_is_rejected(): void
    {
        $budi = User::factory()->create(['email' => 'budi@example.com']);
        User::factory()->create(['email' => 'siti@example.com']);
        $token = Password::createToken($budi);

        $this->postJson('/api/v1/auth/reset-password', $this->resetPayload('made-up-token', 'budi@example.com'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->postJson('/api/v1/auth/reset-password', $this->resetPayload($token, 'siti@example.com'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_reset_validates_the_new_password(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', $this->resetPayload($token, 'budi@example.com', 'pendek'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'budi@example.com',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'berbeda123',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }
}
