<?php

return [
    'admin_allowlist' => [
        'emails' => array_filter(array_map('trim', explode(',', env('PROVIDER_ADMIN_ALLOWLIST', '')))),
        'user_ids' => array_filter(array_map('intval', explode(',', env('PROVIDER_ADMIN_ID_ALLOWLIST', '')))),
    ],
    'enable_mock_payments' => (bool) env('PROVIDER_ENABLE_MOCK_PAYMENTS', false),
];
