<?php

namespace App\Modules\Verification\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class VerificationServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];
}
