<?php

return [
    'moodle' => [
        'base_url' => env('MOODLE_BASE_URL', 'https://moodle.samykhalil.me'),
        'token' => env('MOODLE_TOKEN'),
        'prefix' => 'SISPOC-',
        'category' => 'Cornerstone SIS POC',
        'student_role_id' => env('MOODLE_STUDENT_ROLE_ID', 5),
        'enabled' => env('MOODLE_SYNC_ENABLED', false),
    ],
    'payments' => ['driver' => 'sandbox', 'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET')],
    'sms' => ['driver' => 'manual'],
];
