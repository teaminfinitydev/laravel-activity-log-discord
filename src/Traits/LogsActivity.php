<?php

namespace teaminfinitydev\ActivityLogDiscord\Traits;

use Illuminate\Database\Eloquent\Model;
use teaminfinitydev\ActivityLogDiscord\Services\ActivityLoggerService;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            static::handleActivityLog($model, 'created');
        });

        static::updated(function ($model) {
            static::handleActivityLog($model, 'updated', $model->getChanges());
        });

        static::deleted(function ($model) {
            static::handleActivityLog($model, 'deleted');
        });

        static::restored(function ($model) {
            static::handleActivityLog($model, 'restored');
        });
    }

    /**
     * Handle activity logging for model events
     */
    protected static function handleActivityLog(Model $model, string $event, array $changes = [])
    {
        try {
            // Check if this event should be logged for this model
            if (!static::shouldLogActivity($model, $event)) {
                return;
            }

            $activityLogger = app(ActivityLoggerService::class);

            switch ($event) {
                case 'created':
                    $activityLogger->logModelCreated($model);
                    break;
                case 'updated':
                    // Only log if there are actual changes
                    if (!empty($changes)) {
                        $activityLogger->logModelUpdated($model, $changes);
                    }
                    break;
                case 'deleted':
                    $activityLogger->logModelDeleted($model);
                    break;
                case 'restored':
                    $activityLogger->log(
                        'model.restored',
                        class_basename($model) . " '{$model->getDisplayName()}' was restored",
                        $model,
                        auth()->user()
                    );
                    break;
            }
        } catch (\Exception $e) {
            // Log the error but don't break the application
            \Log::error('Failed to log activity for model event', [
                'model' => get_class($model),
                'model_id' => $model->getKey(),
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine if activity should be logged for this model and event
     */
    protected static function shouldLogActivity(Model $model, string $event): bool
    {
        // Check if the model has logActivity property
        if (!property_exists($model, 'logActivity')) {
            return false;
        }

        // Check if this specific event should be logged
        if (!is_array($model->logActivity) || !in_array($event, $model->logActivity)) {
            return false;
        }

        // Check if activity logging is globally disabled
        if (!config('activity-log-discord.enabled', true)) {
            return false;
        }

        // Check if this specific event type is enabled in config
        $eventType = 'model.' . $event;
        if (!config("activity-log-discord.events.{$eventType}.enabled", true)) {
            return false;
        }

        // Allow models to define their own shouldLogActivity method for custom logic
        if (method_exists($model, 'shouldLogActivity')) {
            return $model->shouldLogActivity($event);
        }

        return true;
    }

    /**
     * Get display name for the model (can be overridden by models)
     */
    public function getDisplayName(): string
    {
        // Try common name fields
        $nameFields = ['title', 'name', 'label', 'display_name', 'username'];
        
        foreach ($nameFields as $field) {
            if (isset($this->$field) && !empty($this->$field)) {
                return $this->$field;
            }
        }
        
        // Try to get the model class name for better context
        $modelName = class_basename($this);
        
        // Fall back to primary key if available
        if ($this->getKey()) {
            return "{$modelName} #{$this->getKey()}";
        }
        
        // Ultimate fallback if no key is available
        return "{$modelName} (no identifier)";
    }

    /**
     * Get additional properties to include in the activity log
     * Override this method in your model to include custom data
     */
    public function getActivityLogProperties(string $event): array
    {
        return [];
    }
}