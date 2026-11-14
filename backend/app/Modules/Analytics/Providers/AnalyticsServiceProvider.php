<?php

namespace App\Modules\Analytics\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class AnalyticsServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];
}
