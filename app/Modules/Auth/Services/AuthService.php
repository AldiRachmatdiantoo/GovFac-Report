<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Events\UserLoggedIn;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Repositories\UserRepository;
use Carbon\CarbonImmutable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthFactory $auth,
        private readonly TokenFreshness $freshness,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        return $this->users->createCitizen($data);
    }

    /**
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     */
    public function login(string $email, string $password, ?string $ip, ?string $userAgent): array
    {
        $token = $this->guard()->attempt(['email' => $email, 'password' => $password]);

        if (! is_string($token)) {
            // Pesan sengaja sama untuk email tidak dikenal maupun password salah.
            throw new AuthenticationException('Email atau password salah.');
        }

        /** @var User $user */
        $user = $this->guard()->user();

        UserLoggedIn::dispatch($user->id, $ip, $userAgent, CarbonImmutable::now());

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Tukar token (boleh sudah kedaluwarsa, asal masih dalam masa refresh)
     * dengan token baru. Token lama masuk blacklist.
     *
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     */
    public function refresh(): array
    {
        try {
            $token = $this->guard()->refresh();
            /** @var User|null $user */
            $user = $this->guard()->setToken($token)->user();
            $issuedAt = (int) $this->guard()->payload()->get('iat');
        } catch (JWTException) {
            throw new AuthenticationException('Sesi berakhir. Silakan login kembali.');
        }

        // iat dipertahankan saat refresh, jadi token yang terbit sebelum
        // password diganti tetap terdeteksi di sini.
        if ($user === null || $this->freshness->isStale($user, $issuedAt)) {
            $this->logout();

            throw new AuthenticationException('Sesi berakhir. Silakan login kembali.');
        }

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Tidak pernah gagal: token yang hilang atau sudah tidak sah tetap dianggap logout.
     */
    public function logout(): void
    {
        try {
            $this->guard()->logout();
        } catch (JWTException) {
            // Tidak ada token yang perlu di-blacklist.
        }
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = $this->auth->guard('api');

        return $guard;
    }
}
