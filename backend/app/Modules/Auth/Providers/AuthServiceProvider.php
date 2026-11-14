<?php

namespace App\Modules\Auth\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];

    /**
     * Endpoint publik yang rawan disalahgunakan. Tiap endpoint punya jatah
     * sendiri (5 per menit per IP) agar tidak saling menghabiskan.
     *
     * @var list<string>
     */
    private const LIMITED = ['login', 'register', 'forgot-password', 'reset-password'];

    public function boot(): void
    {
        foreach (self::LIMITED as $name) {
            RateLimiter::for("auth-{$name}", fn (Request $request) => Limit::perMinute(5)->by("auth-{$name}|".$request->ip()));
        }

        parent::boot();
    }
}
