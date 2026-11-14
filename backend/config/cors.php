<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | Sesi memakai cookie httpOnly, jadi browser hanya mengirimnya bila
    | `supports_credentials` aktif DAN origin disebut secara eksplisit.
    | Wildcard "*" tidak boleh dipakai bersama credentials.
    |
    | FRONTEND_URL boleh berisi beberapa origin dipisah koma.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRONTEND_URL', 'http://localhost:5173')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'X-Requested-With', 'X-XSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => true,

];
