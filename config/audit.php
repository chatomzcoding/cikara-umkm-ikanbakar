<?php

return [
    'enabled' => (bool) env('AUDIT_LOG_ENABLED', true),

    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'recovery_code',
        'secret',
        'api_key',
    ],
];
