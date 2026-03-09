<?php

return [
    'whatsapp' => [
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'twilio' => [
            'validate_signature' => (bool) env('TWILIO_WHATSAPP_VALIDATE_SIGNATURE', true),
            'from' => env('TWILIO_WHATSAPP_FROM'),
            'status_callback' => env('TWILIO_WHATSAPP_STATUS_CALLBACK'),
            'webhook_url' => env('TWILIO_WHATSAPP_WEBHOOK_URL'),
        ],
        'follow_up_minutes' => (int) env('CRM_WHATSAPP_FOLLOW_UP_MINUTES', 60),
        'reopen_window_days' => (int) env('CRM_LEAD_REOPEN_WINDOW_DAYS', 30),
        'reopen_stage' => env('CRM_LEAD_REOPEN_STAGE', 'contacted'),
    ],
];
