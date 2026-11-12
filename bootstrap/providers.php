<?php

use App\Modules\AiValidation\Providers\AiValidationServiceProvider;
use App\Modules\Analytics\Providers\AnalyticsServiceProvider;
use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Community\Providers\CommunityServiceProvider;
use App\Modules\Government\Providers\GovernmentServiceProvider;
use App\Modules\Notification\Providers\NotificationServiceProvider;
use App\Modules\Report\Providers\ReportServiceProvider;
use App\Modules\Verification\Providers\VerificationServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Modules
    AuthServiceProvider::class,
    NotificationServiceProvider::class,
    ReportServiceProvider::class,
    AiValidationServiceProvider::class,
    CommunityServiceProvider::class,
    VerificationServiceProvider::class,
    GovernmentServiceProvider::class,
    AnalyticsServiceProvider::class,
];
