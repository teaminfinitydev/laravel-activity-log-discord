<?php

namespace teaminfinitydev\ActivityLogDiscord\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'event_type',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'discord_sent',
        'discord_sent_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'discord_sent' => 'boolean',
        'discord_sent_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}