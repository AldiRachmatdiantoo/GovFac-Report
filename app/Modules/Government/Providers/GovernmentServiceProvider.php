<?php

namespace App\Modules\Government\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class GovernmentServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];
}
