<?php

namespace teaminfinitydev\ActivityLogDiscord\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use teaminfinitydev\ActivityLogDiscord\Jobs\SendDiscordNotification;
use teaminfinitydev\ActivityLogDiscord\Models\ActivityLog;

class ActivityLoggerService
{
    public function log(
        string $eventType,
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = []
    ): ActivityLog {
        try {
            $activityLog = ActivityLog::create([
                'event_type' => $eventType,
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject?->getKey(), // Use getKey() instead of ->id for custom primary keys
                'causer_type' => $causer ? get_class($causer) : null,
                'causer_id' => $causer?->getKey(),
                'properties' => $properties,
            ]);

            $this->sendToDiscord($activityLog);

            return $activityLog;
        } catch (\Exception $e) {
            Log::error('Failed to create activity log', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a dummy ActivityLog to prevent breaking the application
            $dummyLog = new ActivityLog();
            $dummyLog->event_type = $eventType;
            $dummyLog->description = $description;
            return $dummyLog;
        }
    }

    public function logUserLogin(Model $user): ActivityLog
    {
        return $this->log(
            'user.login',
            "{$this->getUserDisplayName($user)} logged in",
            $user,
            $user,
            [
                'ip' => request()?->ip() ?? 'unknown',
                'user_agent' => request()?->userAgent() ?? 'unknown',
                'timestamp' => now()->toDateTimeString(),
            ]
        );
    }

    public function logUserLogout(Model $user): ActivityLog
    {
        return $this->log(
            'user.logout',
            "{$this->getUserDisplayName($user)} logged out",
            $user,
            $user,
            [
                'ip' => request()?->ip() ?? 'unknown',
                'timestamp' => now()->toDateTimeString(),
            ]
        );
    }

    public function logModelCreated(Model $model, ?Model $causer = null): ActivityLog
    {
        $modelName = class_basename($model);
        $displayName = $this->getModelDisplayName($model);
        
        return $this->log(
            'model.created',
            "{$modelName} '{$displayName}' was created",
            $model,
            $causer ?? $this->getAuthUser(),
            ['attributes' => $this->sanitizeAttributes($model->toArray())]
        );
    }

    public function logModelUpdated(Model $model, array $changes, ?Model $causer = null): ActivityLog
    {
        $modelName = class_basename($model);
        $displayName = $this->getModelDisplayName($model);
        
        return $this->log(
            'model.updated',
            "{$modelName} '{$displayName}' was updated",
            $model,
            $causer ?? $this->getAuthUser(),
            [
                'changes' => $this->sanitizeAttributes($changes),
                'changed_fields' => array_keys($changes)
            ]
        );
    }

    public function logModelDeleted(Model $model, ?Model $causer = null): ActivityLog
    {
        $modelName = class_basename($model);
        $displayName = $this->getModelDisplayName($model);
        
        return $this->log(
            'model.deleted',
            "{$modelName} '{$displayName}' was deleted",
            $model,
            $causer ?? $this->getAuthUser(),
            ['deleted_attributes' => $this->sanitizeAttributes($model->toArray())]
        );
    }

    public function logWebAppBootup(): ActivityLog
    {
        return $this->log(
            'system.bootup',
            'Web application started successfully',
            null,
            null,
            [
                'environment' => app()->environment(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->toDateTimeString(),
                'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                'server_ip' => request()?->server('SERVER_ADDR') ?? 'unknown',
            ]
        );
    }

    public function testWebhook(): bool
    {
        try {
            $testLog = $this->log(
                'system.test',
                'Discord webhook test message - if you see this, your integration is working correctly!',
                null,
                null,
                [
                    'test_timestamp' => now()->toDateTimeString(),
                    'app_name' => config('app.name'),
                    'environment' => app()->environment(),
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Webhook test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function sendToDiscord(ActivityLog $activityLog): void
    {
        try {
            if (!config("activity-log-discord.events.{$activityLog->event_type}.enabled", true)) {
                return;
            }

            if (!config('activity-log-discord.enabled', true)) {
                return;
            }

            if (config('activity-log-discord.queue_notifications', true)) {
                SendDiscordNotification::dispatch($activityLog)
                    ->onConnection(config('activity-log-discord.queue_connection', 'default'))
                    ->onQueue(config('activity-log-discord.queue_name', 'discord-notifications'));
            } else {
                app(DiscordWebhookService::class)->sendActivityLog($activityLog);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send activity log to Discord', [
                'activity_log_id' => $activityLog->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get authenticated user safely
     */
    protected function getAuthUser(): ?Model
    {
        try {
            return auth()->check() ? auth()->user() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get user display name safely
     */
    protected function getUserDisplayName(Model $user): string
    {
        if (method_exists($user, 'getDisplayName')) {
            return $user->getDisplayName();
        }
        
        return $user->name ?? $user->email ?? "User #{$user->getKey()}";
    }

    /**
     * Get model display name safely
     */
    protected function getModelDisplayName(Model $model): string
    {
        if (method_exists($model, 'getDisplayName')) {
            return $model->getDisplayName();
        }
        
        // Try common name fields
        $nameFields = ['title', 'name', 'label', 'display_name'];
        foreach ($nameFields as $field) {
            if (isset($model->$field) && !empty($model->$field)) {
                return $model->$field;
            }
        }
        
        return "#{$model->getKey()}";
    }

    /**
     * Sanitize attributes to remove sensitive data
     */
    protected function sanitizeAttributes(array $attributes): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'token', 'secret', 'api_key', 
            'private_key', 'access_token', 'refresh_token', 'remember_token',
            'two_factor_secret', 'two_factor_recovery_codes'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = '[HIDDEN]';
            }
        }

        // Limit the size of each attribute value
        foreach ($attributes as $key => $value) {
            if (is_string($value) && strlen($value) > 500) {
                $attributes[$key] = substr($value, 0, 497) . '...';
            }
        }

        return $attributes;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}