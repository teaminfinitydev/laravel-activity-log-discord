<?php

namespace teaminfinitydev\ActivityLogDiscord;

use Illuminate\Support\ServiceProvider;
use teaminfinitydev\ActivityLogDiscord\Services\DiscordWebhookService;
use teaminfinitydev\ActivityLogDiscord\Services\ActivityLoggerService;
use teaminfinitydev\ActivityLogDiscord\Console\Commands\TestWebhookCommand;

class ActivityLogDiscordServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/activity-log-discord.php', 'activity-log-discord');
        
        $this->app->singleton(DiscordWebhookService::class, function ($app) {
            return new DiscordWebhookService(
                config('activity-log-discord.webhook_url'),
                config('activity-log-discord.bot_name', 'Activity Logger'),
                config('activity-log-discord.avatar_url')
            );
        });

        $this->app->singleton(ActivityLoggerService::class, function ($app) {
            return new ActivityLoggerService();
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/activity-log-discord.php' => config_path('activity-log-discord.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'migrations');

            // Register console commands
            $this->commands([
                TestWebhookCommand::class,
            ]);
        }

        // Send bootup message if enabled and in allowed environment
        $this->registerBootupMessage();
    }

    protected function registerBootupMessage(): void
    {
        if (!config('activity-log-discord.send_bootup_message', false)) {
            return;
        }

        $allowedEnvironments = config('activity-log-discord.bootup_environments', ['production', 'staging']);
        if (!in_array(app()->environment(), $allowedEnvironments)) {
            return;
        }

        // Only send bootup message for web requests, not console commands
        if (!$this->app->runningInConsole()) {
            // Use a callback to ensure this runs after the application is fully booted
            $this->app->booted(function () {
                try {
                    $activityLogger = app(ActivityLoggerService::class);
                    $activityLogger->logWebAppBootup();
                } catch (\Exception $e) {
                    // Log error but don't crash the application
                    \Log::error('Failed to send bootup message to Discord', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });
        }
    }
}