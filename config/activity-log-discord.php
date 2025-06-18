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
    
    /*
    |--------------------------------------------------------------------------
    | Event Filtering
    |--------------------------------------------------------------------------
    */
    'events' => [
        'user.login' => [
            'enabled' => true,
            'color' => 0x00ff00, // Green
            'icon' => '🔐',
        ],
        'user.logout' => [
            'enabled' => true,
            'color' => 0xff9900, // Orange
            'icon' => '🚪',
        ],
        'user.register' => [
            'enabled' => true,
            'color' => 0x0099ff, // Blue
            'icon' => '👋',
        ],
        'model.created' => [
            'enabled' => true,
            'color' => 0x00ff00, // Green
            'icon' => '➕',
        ],
        'model.updated' => [
            'enabled' => true,
            'color' => 0xffff00, // Yellow
            'icon' => '✏️',
        ],
        'model.deleted' => [
            'enabled' => true,
            'color' => 0xff0000, // Red
            'icon' => '🗑️',
        ],
        'custom' => [
            'enabled' => true,
            'color' => 0x9900ff, // Purple
            'icon' => '📝',
        ],
    ],
];