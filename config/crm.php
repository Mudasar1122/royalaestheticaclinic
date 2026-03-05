<?php

return [
    'whatsapp' => [
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'follow_up_minutes' => (int) env('CRM_WHATSAPP_FOLLOW_UP_MINUTES', 60),
        'reopen_window_days' => (int) env('CRM_LEAD_REOPEN_WINDOW_DAYS', 30),
        'reopen_stage' => env('CRM_LEAD_REOPEN_STAGE', 'contacted'),
    ],
];
