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
ACTIVITY_LOG_LEVEL=info
```

### Advanced Configuration

Customize the package behavior by editing `config/activity-log-discord.php`:

```php
return [
    'webhook_url' => env('DISCORD_WEBHOOK_URL'),
    'bot_name' => env('DISCORD_BOT_NAME', 'Activity Logger'),
    'enabled' => env('ACTIVITY_LOG_DISCORD_ENABLED', true),
    'queue_notifications' => env('ACTIVITY_LOG_QUEUE', true),
    
    'events' => [
        'user.login' => [
            'enabled' => true,
            'color' => 0x00ff00, // Green
            'icon' => '🔐',
        ],
        'model.created' => [
            'enabled' => true,
            'color' => 0x00ff00, // Green
            'icon' => '➕',
        ],
        // ... more events
    ],
];
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
    protected $logActivity = ['created', 'updated', 'deleted'];
    
    // Optional: Customize the display name
    public function getDisplayName(): string
    {
        return $this->title;
    }
}
```

Now every time a `Post` is created, updated, or deleted, it will be automatically logged and sent to Discord!

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

### Custom Event Types

Create your own event types with custom formatting:

```php
// In a controller or service
ActivityLogger::log(
    'payment.failed',
    "Payment failed for order #{$order->id}",
    $order,
    $user,
    [
        'amount' => $order->total,
        'error_code' => $paymentError->code,
        'gateway' => 'stripe'
    ]
);
```

### Advanced Usage

#### Conditional Logging

```php
// Only log in production
if (app()->environment('production')) {
    ActivityLogger::log('sensitive.action', 'Sensitive action performed');
}

// Log with additional context
ActivityLogger::log(
    'api.request',
    'External API called',
    null,
    auth()->user(),
    [
        'endpoint' => '/api/users',
        'response_time' => 150,
        'status_code' => 200
    ]
);
```

#### Custom Display Names

Implement `getDisplayName()` method in your models for better Discord formatting:

```php
class User extends Model
{
    use LogsActivity;
    
    public function getDisplayName(): string
    {
        return "{$this->name} ({$this->email})";
    }
}
```

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
```

### Model Created Event
```
➕ Model Created
Post was created

Performed by: Jane Doe (jane@example.com)
Subject: My First Blog Post
Details:
Title: My First Blog Post
Category: Technology
Status: Published
```

## 🧪 Testing

Run the package tests:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

Check code style:

```bash
composer format
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
```

Don't forget to run your queue workers:

```bash
php artisan queue:work
```

## 🚨 Troubleshooting

### Common Issues

**Discord messages not sending:**
- Verify your webhook URL is correct
- Check that `ACTIVITY_LOG_DISCORD_ENABLED=true`
- Ensure your Discord webhook has proper permissions

**Queue jobs failing:**
- Check your queue configuration
- Verify queue workers are running
- Check Laravel logs for detailed error messages

**Database errors:**
- Run `php artisan migrate` to ensure tables exist
- Check database connection configuration

### Debug Mode

Enable debug logging by setting log level in your `.env`:

```env
ACTIVITY_LOG_LEVEL=debug
```

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Check code style: `composer format`

## 🔒 Security

If you discover any security-related issues, please email security@codenexa.online instead of using the issue tracker.

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🙏 Credits

- [Your Name](https://github.com/teaminfinitydev)
- [All Contributors](../../contributors)

## 💡 Support

- **Documentation**: [Full documentation](https://teaminfinitydev.github.io/laravel-activity-log-discord)
- **Issues**: [GitHub Issues](https://github.com/teaminfinitydev/laravel-activity-log-discord/issues)
- **Discussions**: [GitHub Discussions](https://github.com/teaminfinitydev/laravel-activity-log-discord/discussions)

---

⭐ **Found this package useful? Give it a star on GitHub!** ⭐
