<?php

namespace teaminfinitydev\ActivityLogDiscord\Services;

use Illuminate\Database\Eloquent\Model;
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
        $activityLog = ActivityLog::create([
            'event_type' => $eventType,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'causer_type' => $causer ? get_class($causer) : null,
            'causer_id' => $causer?->id,
            'properties' => $properties,
        ]);

        $this->sendToDiscord($activityLog);

        return $activityLog;
    }

    public function logUserLogin(Model $user): ActivityLog
    {
        return $this->log(
            'user.login',
            "{$user->name} logged in",
            $user,
            $user,
            [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]
        );
    }

    public function logUserLogout(Model $user): ActivityLog
    {
        return $this->log(
            'user.logout',
            "{$user->name} logged out",
            $user,
            $user
        );
    }

    public function logModelCreated(Model $model, ?Model $causer = null): ActivityLog
    {
        return $this->log(
            'model.created',
            class_basename($model) . ' was created',
            $model,
            $causer ?? auth()->user(),
            ['attributes' => $model->toArray()]
        );
    }

    public function logModelUpdated(Model $model, array $changes, ?Model $causer = null): ActivityLog
    {
        return $this->log(
            'model.updated',
            class_basename($model) . ' was updated',
            $model,
            $causer ?? auth()->user(),
            ['changes' => $changes]
        );
    }

    public function logModelDeleted(Model $model, ?Model $causer = null): ActivityLog
    {
        return $this->log(
            'model.deleted',
            class_basename($model) . ' was deleted',
            $model,
            $causer ?? auth()->user()
        );
    }

    protected function sendToDiscord(ActivityLog $activityLog): void
    {
        if (!config("activity-log-discord.events.{$activityLog->event_type}.enabled", true)) {
            return;
        }

        if (config('activity-log-discord.queue_notifications', true)) {
            SendDiscordNotification::dispatch($activityLog)
                ->onConnection(config('activity-log-discord.queue_connection', 'default'));
        } else {
            app(DiscordWebhookService::class)->sendActivityLog($activityLog);
        }
    }
}