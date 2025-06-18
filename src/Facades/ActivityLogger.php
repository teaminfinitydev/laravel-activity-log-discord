<?php

namespace teaminfinitydev\ActivityLogDiscord\Facades;

use Illuminate\Support\Facades\Facade;
use teaminfinitydev\ActivityLogDiscord\Services\ActivityLoggerService;
use teaminfinitydev\ActivityLogDiscord\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static ActivityLog log(string $eventType, string $description, ?Model $subject = null, ?Model $causer = null, array $properties = [])
 * @method static ActivityLog logUserLogin(Model $user)
 * @method static ActivityLog logUserLogout(Model $user)
 * @method static ActivityLog logModelCreated(Model $model, ?Model $causer = null)
 * @method static ActivityLog logModelUpdated(Model $model, array $changes, ?Model $causer = null)
 * @method static ActivityLog logModelDeleted(Model $model, ?Model $causer = null)
 * @method static ActivityLog logWebAppBootup()
 * @method static bool testWebhook()
 *
 * @see \teaminfinitydev\ActivityLogDiscord\Services\ActivityLoggerService
 */
class ActivityLogger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLoggerService::class;
    }
}