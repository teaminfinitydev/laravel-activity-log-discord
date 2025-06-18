<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Discord Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook_url' => env('DISCORD_WEBHOOK_URL'),
    'bot_name' => env('DISCORD_BOT_NAME', 'Activity Logger'),
    'avatar_url' => env('DISCORD_AVATAR_URL'),
    
    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'enabled' => env('ACTIVITY_LOG_DISCORD_ENABLED', true),
    'log_level' => env('ACTIVITY_LOG_LEVEL', 'info'), // info, warning, error
    'queue_notifications' => env('ACTIVITY_LOG_QUEUE', true),
    'queue_connection' => env('ACTIVITY_LOG_QUEUE_CONNECTION', 'default'),
    'queue_name' => env('ACTIVITY_LOG_QUEUE_NAME', 'discord-notifications'),
    
    /*
    |--------------------------------------------------------------------------
    | Application Bootup Configuration
    |--------------------------------------------------------------------------
    */
    'send_bootup_message' => env('ACTIVITY_LOG_SEND_BOOTUP', false),
    'bootup_environments' => ['production', 'staging'], // Only send bootup messages in these environments
    
    /*
    |--------------------------------------------------------------------------
    | Event Filtering
    |--------------------------------------------------------------------------
    */
    'events' => [
        'user.login' => [
            'enabled' => env('ACTIVITY_LOG_USER_LOGIN_ENABLED', true),
            'color' => 0x00ff00, // Green
            'icon' => '🔐',
        ],
        'user.logout' => [
            'enabled' => env('ACTIVITY_LOG_USER_LOGOUT_ENABLED', true),
            'color' => 0xff9900, // Orange
            'icon' => '🚪',
        ],
        'user.register' => [
            'enabled' => env('ACTIVITY_LOG_USER_REGISTER_ENABLED', true),
            'color' => 0x0099ff, // Blue
            'icon' => '👋',
        ],
        'model.created' => [
            'enabled' => env('ACTIVITY_LOG_MODEL_CREATED_ENABLED', true),
            'color' => 0x00ff00, // Green
            'icon' => '➕',
        ],
        'model.updated' => [
            'enabled' => env('ACTIVITY_LOG_MODEL_UPDATED_ENABLED', true),
            'color' => 0xffff00, // Yellow
            'icon' => '✏️',
        ],
        'model.deleted' => [
            'enabled' => env('ACTIVITY_LOG_MODEL_DELETED_ENABLED', true),
            'color' => 0xff0000, // Red
            'icon' => '🗑️',
        ],
        'system.bootup' => [
            'enabled' => env('ACTIVITY_LOG_SYSTEM_BOOTUP_ENABLED', true),
            'color' => 0x00ffff, // Cyan
            'icon' => '🚀',
        ],
        'system.test' => [
            'enabled' => true,
            'color' => 0x9900ff, // Purple
            'icon' => '🧪',
        ],
        'custom' => [
            'enabled' => env('ACTIVITY_LOG_CUSTOM_ENABLED', true),
            'color' => 0x9900ff, // Purple
            'icon' => '📝',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Fields
    |--------------------------------------------------------------------------
    | These fields will be masked in activity logs for security
    */
    'sensitive_fields' => [
        'password', 'password_confirmation', 'token', 'secret', 'api_key',
        'private_key', 'access_token', 'refresh_token', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes', 'credit_card',
        'ssn', 'social_security_number', 'bank_account'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Message Limits
    |--------------------------------------------------------------------------
    | Discord has message limits, these help prevent errors
    */
    'limits' => [
        'title_max_length' => 256,
        'description_max_length' => 2048,
        'field_value_max_length' => 1024,
        'max_properties' => 10,
        'max_property_length' => 100,
    ],
];