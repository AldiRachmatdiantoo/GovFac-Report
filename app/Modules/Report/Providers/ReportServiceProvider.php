<?php

namespace App\Modules\Report\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class ReportServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];
}
