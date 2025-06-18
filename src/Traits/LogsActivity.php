<?php

namespace teaminfinitydev\ActivityLogDiscord\Traits;

use teaminfinitydev\ActivityLogDiscord\Services\ActivityLoggerService;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            if (property_exists($model, 'logActivity') && in_array('created', $model->logActivity)) {
                app(ActivityLoggerService::class)->logModelCreated($model);
            }
        });

        static::updated(function ($model) {
            if (property_exists($model, 'logActivity') && in_array('updated', $model->logActivity)) {
                app(ActivityLoggerService::class)->logModelUpdated($model, $model->getChanges());
            }
        });

        static::deleted(function ($model) {
            if (property_exists($model, 'logActivity') && in_array('deleted', $model->logActivity)) {
                app(ActivityLoggerService::class)->logModelDeleted($model);
            }
        });
    }
}