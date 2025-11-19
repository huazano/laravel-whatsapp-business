<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure your WhatsApp Business Cloud API credentials.
    | You can get these credentials from the Meta Business Suite.
    |
    */

    'from_phone_number_id' => env('WHATSAPP_FROM_PHONE_NUMBER_ID'),

    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),

    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'your_verify_token'),

    'app_secret' => env('WHATSAPP_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Version
    |--------------------------------------------------------------------------
    |
    | The version of the WhatsApp Cloud API to use.
    |
    */

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    /*
    |--------------------------------------------------------------------------
    | Default User Role
    |--------------------------------------------------------------------------
    |
    | The default role to assign to new WhatsApp users when they are
    | automatically registered via webhook.
    |
    */

    'default_user_role' => env('WHATSAPP_DEFAULT_USER_ROLE', 'guest'),
];
