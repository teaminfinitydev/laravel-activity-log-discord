# Laravel Activity Log Discord

[![Latest Version on Packagist](https://img.shields.io/packagist/v/teaminfinitydev/laravel-activity-log-discord.svg?style=flat-square)](https://packagist.org/packages/teaminfinitydev/laravel-activity-log-discord)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/teaminfinitydev/laravel-activity-log-discord/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/teaminfinitydev/laravel-activity-log-discord/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/teaminfinitydev/laravel-activity-log-discord/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/teaminfinitydev/laravel-activity-log-discord/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/teaminfinitydev/laravel-activity-log-discord.svg?style=flat-square)](https://packagist.org/packages/teaminfinitydev/laravel-activity-log-discord)

A powerful Laravel package that logs user activities and system events, then sends beautiful notifications to Discord channels via webhooks. Perfect for monitoring your application's important events in real-time.

## 🌟 Features

- **📝 Comprehensive Activity Logging** - Track user actions, model changes, and custom events
- **🎯 Discord Integration** - Send rich embed notifications to Discord channels
- **🚀 Queue Support** - Asynchronous processing for better performance
- **🎨 Customizable Embeds** - Configurable colors, icons, and formatting
- **🔧 Easy Configuration** - Environment-based settings with sensible defaults
- **📊 Database Storage** - Store activity logs with full relationship tracking
- **🏷️ Auto-Model Tracking** - Simple trait-based automatic logging
- **🔍 Event Filtering** - Enable/disable specific event types
- **⚡ Performance Optimized** - Efficient database queries and caching
- **🧪 Test Webhook** - Built-in webhook testing functionality
- **🚀 Bootup Messages** - Optional application startup notifications
- **🛡️ Error Handling** - Robust error handling and logging
- **🔒 Security** - Automatic sensitive data masking

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- GuzzleHTTP 7.0 or higher

## 🚀 Installation

Install the package via Composer:

```bash
composer require teaminfinitydev/laravel-activity-log-discord
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="teaminfinitydev\ActivityLogDiscord\ActivityLogDiscordServiceProvider" --tag="config"
```

Publish and run the migrations:

```bash
php artisan vendor:publish --provider="teaminfinitydev\ActivityLogDiscord\ActivityLogDiscordServiceProvider" --tag="migrations"
php artisan migrate
```

## ⚙️ Configuration

### Discord Webhook Setup

1. Go to your Discord server settings
2. Navigate to **Integrations** → **Webhooks**
3. Create a new webhook for your desired channel
4. Copy the webhook URL

### Environment Configuration

Add the following to your `.env` file:

```env
# Discord Configuration
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/your-webhook-url-here
DISCORD_BOT_NAME="App Activity Logger"
DISCORD_AVATAR_URL=https://your-app.com/logo.png

# Package Configuration
ACTIVITY_LOG_DISCORD_ENABLED=true
ACTIVITY_LOG_QUEUE=true
ACTIVITY_LOG_QUEUE_CONNECTION=default
ACTIVITY_LOG_QUEUE_NAME=discord-notifications
ACTIVITY_LOG_LEVEL=info

# Bootup Messages (Optional)
ACTIVITY_LOG_SEND_BOOTUP=false
```

### SSL Configuration

By default, this package uses SSL verification when connecting to Discord webhooks. This is recommended for production environments.

#### For Production Environments (Default):

The package uses the following Guzzle configuration by default, which includes SSL verification:

```php
$this->client = new Client([
    'timeout' => 30,
    'connect_timeout' => 10,
    'verify' => true, // SSL verification enabled
]);
```

#### For Local Development or Troubleshooting:

If you're having connection issues in a local development environment or behind certain corporate firewalls, you may need to disable SSL verification temporarily:

1. Open `src/Services/DiscordWebhookService.php`
2. Find the client initialization in the constructor
3. Change the `verify` option to `false`:

```php
$this->client = new Client([
    'timeout' => 30,
    'connect_timeout' => 10,
    'verify' => false, // SSL verification disabled
]);
```

⚠️ **IMPORTANT SECURITY WARNING**: Only disable SSL verification in local development environments. Never disable SSL verification in production as it makes your application vulnerable to man-in-the-middle attacks.

### Test Your Integration

After configuration, test your webhook integration:

```bash
# Basic test
php artisan activity-log:test-webhook

# Detailed test with configuration info
php artisan activity-log:test-webhook --detailed
```

## 📖 Usage

### Basic Usage

#### Manual Logging

```php
use teaminfinitydev\ActivityLogDiscord\Facades\ActivityLogger;

// Simple event logging
ActivityLogger::log('user.action', 'User performed a custom action');

// Detailed logging with subject and causer
ActivityLogger::log(
    'order.completed',
    'Order #1234 has been completed',
    $order,        // Subject (the order)
    $user,         // Causer (who performed the action)
    ['total' => 99.99, 'items' => 3] // Additional properties
);
```

#### Built-in Helper Methods

```php
// User activity logging
ActivityLogger::logUserLogin($user);
ActivityLogger::logUserLogout($user);

// Model activity logging
ActivityLogger::logModelCreated($post, $user);
ActivityLogger::logModelUpdated($post, $changes, $user);
ActivityLogger::logModelDeleted($post, $user);

// System events
ActivityLogger::logWebAppBootup();

// Test webhook
$success = ActivityLogger::testWebhook();
```

### Automatic Model Tracking

Add the `LogsActivity` trait to your models for automatic tracking:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use teaminfinitydev\ActivityLogDiscord\Traits\LogsActivity;

class Post extends Model
{
    use LogsActivity;
    
    // Specify which events to log
    protected $logActivity = ['created', 'updated', 'deleted', 'restored'];
    
    // Optional: Customize the display name
    public function getDisplayName(): string
    {
        return $this->title;
    }
    
    // Optional: Custom logging conditions
    public function shouldLogActivity(string $event): bool
    {
        // Don't log updates if only timestamps changed
        if ($event === 'updated') {
            return count($this->getDirty()) > 2; // more than created_at and updated_at
        }
        
        return true;
    }
    
    // Optional: Additional properties for activity log
    public function getActivityLogProperties(string $event): array
    {
        return [
            'category' => $this->category,
            'status' => $this->status,
        ];
    }
}
```

### User Authentication Logging

Track user login/logout events automatically:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use teaminfinitydev\ActivityLogDiscord\Facades\ActivityLogger;

class LoginController extends Controller
{
    protected function authenticated(Request $request, $user)
    {
        ActivityLogger::logUserLogin($user);
    }
    
    public function logout(Request $request)
    {
        if (auth()->check()) {
            ActivityLogger::logUserLogout(auth()->user());
        }
        
        $this->guard()->logout();
        $request->session()->invalidate();
        
        return redirect('/');
    }
}
```

### Application Bootup Monitoring

Enable bootup messages to monitor when your application starts:

```env
ACTIVITY_LOG_SEND_BOOTUP=true
```

This will send a message to Discord whenever your web application boots up, useful for monitoring deployments and server restarts.

## 🧪 Troubleshooting

### Connection Issues

If you're experiencing issues connecting to Discord webhooks, try the following solutions:

#### 1. Check Your Webhook URL

Ensure your Discord webhook URL is correctly formatted and valid. It should look like:
```
https://discord.com/api/webhooks/[webhook_id]/[webhook_token]
```

#### 2. SSL Certificate Issues

If you receive a "No response from Discord (connection error)" error, you might be having SSL certificate issues, especially in a local development environment.

**Temporary Fix (Development Only):**

Modify the `DiscordWebhookService.php` file to disable SSL verification:

```php
public function __construct(?string $webhookUrl, string $botName = 'Activity Logger', ?string $avatarUrl = null)
{
    $this->client = new Client([
        'timeout' => 30,
        'connect_timeout' => 10,
        'verify' => false, // Disable SSL verification for testing
    ]);
    // Rest of the constructor...
}
```

**⚠️ WARNING:** Re-enable SSL verification (`verify => true`) before deploying to production!

#### 3. Firewall or Network Issues

- Ensure your server/local environment can access external services
- Check if your firewall allows outgoing connections to Discord (ports 443/80)
- Try testing with a different internet connection

#### 4. Detailed Debug Logging

Enable detailed logging to see what's happening with the connection:

```php
try {
    // In your DiscordWebhookService.php
    Log::debug('Attempting to connect to Discord webhook', [
        'webhook_url' => $this->maskWebhookUrl($this->webhookUrl),
    ]);
    
    $response = $this->client->post($this->webhookUrl, [
        // your existing options
        'debug' => true, // Add this to see detailed request/response info
    ]);
} catch (RequestException $e) {
    Log::error('Discord webhook error details', [
        'request_exception_details' => method_exists($e, 'getHandlerContext') ? $e->getHandlerContext() : 'Not available',
    ]);
}
```

Then check your Laravel logs for more information.

## 🎨 Discord Message Examples

The package sends rich embed messages to Discord that look like this:

### User Login Event
```
🔐 User Login
User john@example.com logged in

Performed by: John Doe (john@example.com)
Details:
IP: 192.168.1.100
User Agent: Mozilla/5.0...
Timestamp: 2024-01-15 14:30:22
```

### Application Bootup Event
```
🚀 System Bootup
Web application started successfully

Details:
Environment: production
PHP Version: 8.2.0
Laravel Version: 10.0.0
Memory Usage: 32.5 MB
Server Time: 2024-01-15 14:30:22
```

### Model Created Event
```
➕ Model Created
Post 'My First Blog Post' was created

Performed by: Jane Doe (jane@example.com)
Subject: My First Blog Post
Details:
Title: My First Blog Post
Category: Technology
Status: Published
```

## 📊 Database Schema

The package creates an `activity_logs` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `event_type` | string | Type of event (e.g., 'user.login') |
| `description` | text | Human-readable description |
| `subject_type` | string | Model class of the subject |
| `subject_id` | bigint | ID of the subject model |
| `causer_type` | string | Model class of the causer |
| `causer_id` | bigint | ID of the causer model |
| `properties` | json | Additional event data |
| `discord_sent` | boolean | Whether sent to Discord |
| `discord_sent_at` | timestamp | When sent to Discord |
| `created_at` | timestamp | When the log was created |
| `updated_at` | timestamp | When the log was updated |

## 🔧 Customization

### Custom Event Colors and Icons

Modify `config/activity-log-discord.php` to customize event appearance:

```php
'events' => [
    'user.login' => [
        'enabled' => true,
        'color' => 0x00ff00, // Green
        'icon' => '🔐',
    ],
    'order.failed' => [
        'enabled' => true,
        'color' => 0xff0000, // Red
        'icon' => '❌',
    ],
],
```

### Queue Configuration

For high-traffic applications, enable queue processing:

```php
// config/activity-log-discord.php
'queue_notifications' => true,
'queue_connection' => 'redis', // or your preferred connection
'queue_name' => 'discord-notifications',
```

Don't forget to run your queue workers:

```bash
php artisan queue:work
```


## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
