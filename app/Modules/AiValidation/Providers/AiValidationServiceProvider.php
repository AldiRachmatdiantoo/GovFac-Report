<?php

namespace App\Modules\AiValidation\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class AiValidationServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];
}
