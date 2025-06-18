<?php

namespace teaminfinitydev\ActivityLogDiscord\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use teaminfinitydev\ActivityLogDiscord\Models\ActivityLog;
use teaminfinitydev\ActivityLogDiscord\Services\DiscordWebhookService;

class SendDiscordNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [10, 30, 60]; // Retry after 10s, 30s, then 60s
    }

    public function __construct(
        public ActivityLog $activityLog
    ) {
        $this->onQueue(config('activity-log-discord.queue_name', 'discord-notifications'));
    }

    public function handle(DiscordWebhookService $discordService): void
    {
        try {
            // Check if the activity log still exists (it might have been deleted)
            if (!$this->activityLog->exists) {
                Log::warning('Activity log no longer exists, skipping Discord notification', [
                    'activity_log_id' => $this->activityLog->id,
                ]);
                return;
            }

            // Check if it was already sent
            if ($this->activityLog->discord_sent) {
                Log::info('Discord notification already sent, skipping', [
                    'activity_log_id' => $this->activityLog->id,
                ]);
                return;
            }

            // Check if Discord is enabled
            if (!config('activity-log-discord.enabled', true)) {
                Log::info('Discord notifications are disabled, skipping', [
                    'activity_log_id' => $this->activityLog->id,
                ]);
                return;
            }

            // Check if this specific event type is enabled
            if (!config("activity-log-discord.events.{$this->activityLog->event_type}.enabled", true)) {
                Log::info('Event type is disabled, skipping Discord notification', [
                    'activity_log_id' => $this->activityLog->id,
                    'event_type' => $this->activityLog->event_type,
                ]);
                return;
            }

            $success = $discordService->sendActivityLog($this->activityLog);

            if ($success) {
                Log::info('Discord notification sent successfully', [
                    'activity_log_id' => $this->activityLog->id,
                    'event_type' => $this->activityLog->event_type,
                ]);
            } else {
                Log::warning('Failed to send Discord notification', [
                    'activity_log_id' => $this->activityLog->id,
                    'event_type' => $this->activityLog->event_type,
                ]);
                
                // Don't throw an exception here as the webhook service already logs the specific error
                // This prevents the job from retrying unnecessarily for configuration issues
            }

        } catch (\Exception $e) {
            Log::error('Exception occurred while sending Discord notification', [
                'activity_log_id' => $this->activityLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Discord notification job failed permanently', [
            'activity_log_id' => $this->activityLog->id ?? 'unknown',
            'event_type' => $this->activityLog->event_type ?? 'unknown',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Optionally, you could mark the activity log as failed
        try {
            if ($this->activityLog && $this->activityLog->exists) {
                $this->activityLog->update([
                    'discord_sent' => false,
                    'discord_sent_at' => null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update activity log after job failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine if the job should be retried based on the exception.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10); // Stop retrying after 10 minutes
    }
}