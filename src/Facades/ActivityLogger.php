<?php

namespace teaminfinitydev\ActivityLogDiscord\Facades;

use Illuminate\Support\Facades\Facade;
use teaminfinitydev\ActivityLogDiscord\Services\ActivityLoggerService;

class ActivityLogger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLoggerService::class;
    }
}
