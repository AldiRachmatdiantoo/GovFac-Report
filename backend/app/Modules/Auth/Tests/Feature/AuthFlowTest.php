<?php

namespace App\Modules\Auth\Tests\Feature;

use App\Modules\Auth\Events\UserLoggedIn;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Notifications\ResetPasswordLink;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'rahasia-123';

    /**
     * Kirim permintaan JSON dengan cookie mentah (grup "api" tidak mengenkripsi cookie).
     *
     * Guard dan pengelola JWT adalah singleton yang menyimpan user dan token dari
     * permintaan sebelumnya. Di produksi tiap permintaan adalah proses baru; di tes
     * kita harus mengosongkannya sendiri supaya tiap panggilan benar-benar terisolasi.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $cookies
     * @param  array<string, string>  $headers
     */
    private function api(string $method, string $uri, array $data = [], array $cookies = [], array $headers = []): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $this->app['tymon.jwt']->unsetToken();

        $server = $this->transformHeadersToServerVars($headers + [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        return $this->call($method, '/api/v1'.$uri, [], $cookies, [], $server, $data === [] ? null : json_encode($data));
    }

    /**
     * @return array{access_token: string, 'XSRF-TOKEN': string}
     */
    private function sessionCookies(TestResponse $response): array
    {
        return [
            'access_token' => (string) $response->getCookie('access_token', false)?->getValue(),
            'XSRF-TOKEN' => (string) $response->getCookie('XSRF-TOKEN', false)?->getValue(),
        ];
    }

    /**
     * @return array{access_token: string, 'XSRF-TOKEN': string}
     */
    private function loginAs(User $user): array
    {
        $response = $this->api('POST', '/auth/login', ['email' => $user->email, 'password' => self::PASSWORD]);
        $response->assertOk();

        return $this->sessionCookies($response);
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['password' => self::PASSWORD]);
    }

    // ---------------------------------------------------------------- register

    public function test_register_creates_a_citizen_account(): void
    {
        $response = $this->api('POST', '/auth/register', [
            'name' => 'Budi Santoso',
            'email' => '  Budi@Example.com ',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'budi@example.com')
            ->assertJsonPath('data.role', 'user')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertNull($response->getCookie('access_token', false), 'Registrasi tidak otomatis login.');
    }

    public function test_register_ignores_a_role_sent_by_the_client(): void
    {
        $this->api('POST', '/auth/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'role' => 'admin',
        ])->assertCreated()->assertJsonPath('data.role', 'user');

        $this->assertSame(UserRole::User, User::where('email', 'penyusup@example.com')->firstOrFail()->role);
    }

    public function test_register_rejects_duplicate_email_and_weak_password(): void
    {
        $this->user(['email' => 'budi@example.com']);

        $this->api('POST', '/auth/register', [
            'name' => 'Budi',
            'email' => 'BUDI@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->api('POST', '/auth/register', [
            'name' => 'Ani',
            'email' => 'ani@example.com',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->api('POST', '/auth/register', [
            'name' => 'Ani',
            'email' => 'ani@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => 'tidak-sama',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    // ------------------------------------------------------------------- login

    public function test_login_sets_httponly_jwt_cookie_and_never_returns_the_token_in_the_body(): void
    {
        Event::fake([UserLoggedIn::class]);
        $user = $this->user();

        $response = $this->api('POST', '/auth/login', ['email' => $user->email, 'password' => self::PASSWORD], headers: [
            'User-Agent' => 'PHPUnit Browser',
        ]);

        $response->assertOk()->assertJsonPath('data.user.id', $user->id);

        $access = $response->getCookie('access_token', false);
        $xsrf = $response->getCookie('XSRF-TOKEN', false);

        $this->assertNotNull($access);
        $this->assertTrue($access->isHttpOnly());
        $this->assertSame('lax', $access->getSameSite());
        $this->assertNotNull($xsrf);
        $this->assertFalse($xsrf->isHttpOnly(), 'Frontend harus bisa membaca XSRF-TOKEN.');

        $this->assertStringNotContainsString($access->getValue(), (string) $response->getContent());
        $this->assertStringNotContainsString('token', (string) $response->getContent());

        Event::assertDispatched(UserLoggedIn::class, fn (UserLoggedIn $event) => $event->userId === $user->id
            && $event->userAgent === 'PHPUnit Browser');
    }

    public function test_login_failure_looks_the_same_for_unknown_email_and_wrong_password(): void
    {
        Event::fake([UserLoggedIn::class]);
        $user = $this->user();

        $wrongPassword = $this->api('POST', '/auth/login', ['email' => $user->email, 'password' => 'salah-total']);
        $unknownEmail = $this->api('POST', '/auth/login', ['email' => 'tidak-ada@example.com', 'password' => self::PASSWORD]);

        $wrongPassword->assertUnauthorized();
        $unknownEmail->assertUnauthorized();
        $this->assertSame($wrongPassword->getContent(), $unknownEmail->getContent());
        $this->assertNull($wrongPassword->getCookie('access_token', false));

        Event::assertNotDispatched(UserLoggedIn::class);
    }

    public function test_login_is_throttled_after_five_attempts_a_minute(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->api('POST', '/auth/login', ['email' => $user->email, 'password' => 'salah-total'])->assertUnauthorized();
        }

        $this->api('POST', '/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertTooManyRequests();
    }

    public function test_each_public_endpoint_has_its_own_rate_limit(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->api('POST', '/auth/forgot-password', ['email' => $user->email])->assertOk();
        }

        $this->api('POST', '/auth/forgot-password', ['email' => $user->email])->assertTooManyRequests();
        $this->api('POST', '/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertOk();
    }

    // ---------------------------------------------------------------------- me

    public function test_me_requires_a_session(): void
    {
        $this->api('GET', '/auth/me')->assertUnauthorized();
        $this->api('GET', '/auth/me', cookies: ['access_token' => 'bukan-jwt'])->assertUnauthorized();
    }

    public function test_me_returns_the_profile_for_a_valid_cookie(): void
    {
        $user = $this->user(['name' => 'Budi']);
        $cookies = $this->loginAs($user);

        $this->api('GET', '/auth/me', cookies: $cookies)
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Budi')
            ->assertJsonPath('data.role', 'user');
    }

    // -------------------------------------------------------------------- csrf

    public function test_state_changing_requests_with_a_session_need_the_xsrf_header(): void
    {
        $cookies = $this->loginAs($this->user());

        $this->api('POST', '/auth/logout', cookies: $cookies)->assertStatus(419);
        $this->api('POST', '/auth/logout', cookies: $cookies, headers: ['X-XSRF-TOKEN' => 'salah'])->assertStatus(419);

        // Sesi masih utuh karena kedua permintaan di atas ditolak.
        $this->api('GET', '/auth/me', cookies: $cookies)->assertOk();
    }

    // ------------------------------------------------------------------ logout

    public function test_logout_clears_cookies_and_blacklists_the_token(): void
    {
        $cookies = $this->loginAs($this->user());

        $response = $this->api('POST', '/auth/logout', cookies: $cookies, headers: ['X-XSRF-TOKEN' => $cookies['XSRF-TOKEN']]);

        $response->assertOk();
        $this->assertTrue($response->getCookie('access_token', false)->isCleared());
        $this->assertTrue($response->getCookie('XSRF-TOKEN', false)->isCleared());

        // Cookie lama yang dicuri sebelum logout tidak boleh bisa dipakai lagi.
        $this->api('GET', '/auth/me', cookies: $cookies)->assertUnauthorized();
    }

    public function test_logout_without_a_session_still_succeeds(): void
    {
        $this->api('POST', '/auth/logout')->assertOk();
    }

    // ----------------------------------------------------------------- refresh

    public function test_refresh_rotates_the_token_and_blacklists_the_old_one(): void
    {
        $user = $this->user();
        $old = $this->loginAs($user);

        $this->travel(1)->minutes();
        $response = $this->api('POST', '/auth/refresh', cookies: $old, headers: ['X-XSRF-TOKEN' => $old['XSRF-TOKEN']]);

        $response->assertOk()->assertJsonPath('data.user.id', $user->id);
        $new = $this->sessionCookies($response);

        $this->assertNotSame($old['access_token'], $new['access_token']);
        $this->assertNotSame($old['XSRF-TOKEN'], $new['XSRF-TOKEN']);
        $this->api('GET', '/auth/me', cookies: $new)->assertOk();
        $this->api('GET', '/auth/me', cookies: $old)->assertUnauthorized();
    }

    public function test_an_expired_access_token_can_still_be_refreshed_within_the_refresh_window(): void
    {
        $cookies = $this->loginAs($this->user());

        $this->travel(16)->minutes();
        $this->api('GET', '/auth/me', cookies: $cookies)->assertUnauthorized();

        $response = $this->api('POST', '/auth/refresh', cookies: $cookies, headers: ['X-XSRF-TOKEN' => $cookies['XSRF-TOKEN']]);
        $response->assertOk();

        $this->api('GET', '/auth/me', cookies: $this->sessionCookies($response))->assertOk();
    }

    public function test_refresh_fails_after_the_refresh_window_and_clears_cookies(): void
    {
        $cookies = $this->loginAs($this->user());

        $this->travel(8)->days();
        $response = $this->api('POST', '/auth/refresh', cookies: $cookies, headers: ['X-XSRF-TOKEN' => $cookies['XSRF-TOKEN']]);

        $response->assertUnauthorized();
        $this->assertTrue($response->getCookie('access_token', false)->isCleared());
    }

    public function test_refresh_without_a_session_is_unauthorized(): void
    {
        $this->api('POST', '/auth/refresh')->assertUnauthorized();
    }

    // ------------------------------------------------- password_changed_at

    public function test_tokens_issued_before_a_password_change_are_rejected(): void
    {
        $user = $this->user();
        $cookies = $this->loginAs($user);
        $xsrf = ['X-XSRF-TOKEN' => $cookies['XSRF-TOKEN']];

        $this->travel(1)->minutes();
        $user->forceFill(['password' => 'password-baru-1', 'password_changed_at' => now()])->save();

        $this->api('GET', '/auth/me', cookies: $cookies)->assertUnauthorized();
        $this->api('POST', '/auth/refresh', cookies: $cookies, headers: $xsrf)->assertUnauthorized();

        // Browser masih mengirim cookie basi; login ulang tetap harus bisa.
        $this->travel(1)->minutes();
        $response = $this->api('POST', '/auth/login', ['email' => $user->email, 'password' => 'password-baru-1'], $cookies, $xsrf);
        $response->assertOk();

        $this->api('GET', '/auth/me', cookies: $this->sessionCookies($response))->assertOk();
    }

    public function test_a_stale_but_expired_token_cannot_be_refreshed_into_a_new_session(): void
    {
        $user = $this->user();
        $cookies = $this->loginAs($user);

        $this->travel(1)->minutes();
        $user->forceFill(['password_changed_at' => now()])->save();
        $this->travel(20)->minutes();

        $this->api('POST', '/auth/refresh', cookies: $cookies, headers: ['X-XSRF-TOKEN' => $cookies['XSRF-TOKEN']])
            ->assertUnauthorized();
    }

    // ------------------------------------------------------ frontend_url, cors

    public function test_reset_password_link_points_at_the_configured_frontend(): void
    {
        config(['app.frontend_url' => 'https://lapor.example.test']);

        $url = (new ResetPasswordLink('token-123'))->url($this->user(['email' => 'budi@example.com']));

        $this->assertStringStartsWith('https://lapor.example.test/reset-password?', $url);
        $this->assertStringContainsString('token=token-123', $url);
    }

    public function test_cors_allows_credentials_only_for_the_frontend_origin(): void
    {
        config(['cors.allowed_origins' => ['https://lapor.example.test']]);

        $preflight = fn (string $origin) => $this->api('OPTIONS', '/auth/login', headers: [
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,x-xsrf-token',
        ]);

        $preflight('https://lapor.example.test')
            ->assertHeader('Access-Control-Allow-Origin', 'https://lapor.example.test')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');

        // Dengan satu origin terdaftar, Laravel selalu menjawab origin itu. Browser
        // lalu menolak karena tidak sama dengan origin pemanggil; yang penting
        // origin asing tidak pernah dipantulkan balik.
        $this->assertNotSame(
            'https://jahat.example.test',
            $preflight('https://jahat.example.test')->headers->get('Access-Control-Allow-Origin'),
        );
    }
}
