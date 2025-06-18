<?php

namespace teaminfinitydev\ActivityLogDiscord\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use teaminfinitydev\ActivityLogDiscord\Models\ActivityLog;
use teaminfinitydev\ActivityLogDiscord\Services\DiscordWebhookService;

class SendDiscordNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ActivityLog $activityLog
    ) {
        $this->onQueue('discord-notifications');
    }

    public function handle(DiscordWebhookService $discordService): void
    {
        $discordService->sendActivityLog($this->activityLog);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Failed to send Discord notification', [
            'activity_log_id' => $this->activityLog->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
