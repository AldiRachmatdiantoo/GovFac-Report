<?php

namespace App\Modules\Community\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class CommunityServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];
}
