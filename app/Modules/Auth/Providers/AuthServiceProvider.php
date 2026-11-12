<?php

namespace App\Modules\Auth\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];
}
