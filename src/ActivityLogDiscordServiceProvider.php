<?php

namespace teaminfinitydev\ActivityLogDiscord;

use Illuminate\Support\ServiceProvider;
use teaminfinitydev\ActivityLogDiscord\Services\DiscordWebhookService;

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
        }
    }
}