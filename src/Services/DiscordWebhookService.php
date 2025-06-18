<?php

namespace teaminfinitydev\ActivityLogDiscord\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use teaminfinitydev\ActivityLogDiscord\Models\ActivityLog;

class DiscordWebhookService
{
    protected Client $client;
    protected string $webhookUrl;
    protected string $botName;
    protected ?string $avatarUrl;

    public function __construct(string $webhookUrl, string $botName = 'Activity Logger', ?string $avatarUrl = null)
    {
        $this->client = new Client(['timeout' => 10]);
        $this->webhookUrl = $webhookUrl;
        $this->botName = $botName;
        $this->avatarUrl = $avatarUrl;
    }

    public function sendActivityLog(ActivityLog $activityLog): bool
    {
        if (!$this->webhookUrl || !config('activity-log-discord.enabled')) {
            return false;
        }

        $embed = $this->buildEmbed($activityLog);
        
        try {
            $response = $this->client->post($this->webhookUrl, [
                'json' => [
                    'username' => $this->botName,
                    'avatar_url' => $this->avatarUrl,
                    'embeds' => [$embed],
                ],
            ]);

            if ($response->getStatusCode() === 204) {
                $activityLog->update([
                    'discord_sent' => true,
                    'discord_sent_at' => now(),
                ]);
                return true;
            }
        } catch (RequestException $e) {
            Log::error('Discord webhook failed', [
                'error' => $e->getMessage(),
                'activity_log_id' => $activityLog->id,
            ]);
        }

        return false;
    }

    protected function buildEmbed(ActivityLog $activityLog): array
    {
        $eventConfig = config("activity-log-discord.events.{$activityLog->event_type}", 
                             config('activity-log-discord.events.custom'));

        $embed = [
            'title' => $this->formatTitle($activityLog),
            'description' => $activityLog->description,
            'color' => $eventConfig['color'] ?? 0x9900ff,
            'timestamp' => $activityLog->created_at->toISOString(),
            'fields' => [],
        ];

        // Add causer information
        if ($activityLog->causer) {
            $embed['fields'][] = [
                'name' => 'Performed by',
                'value' => $this->formatCauser($activityLog->causer),
                'inline' => true,
            ];
        }

        // Add subject information
        if ($activityLog->subject) {
            $embed['fields'][] = [
                'name' => 'Subject',
                'value' => $this->formatSubject($activityLog->subject),
                'inline' => true,
            ];
        }

        // Add properties if available
        if ($activityLog->properties && !empty($activityLog->properties)) {
            $embed['fields'][] = [
                'name' => 'Details',
                'value' => $this->formatProperties($activityLog->properties),
                'inline' => false,
            ];
        }

        return $embed;
    }

    protected function formatTitle(ActivityLog $activityLog): string
    {
        $eventConfig = config("activity-log-discord.events.{$activityLog->event_type}",
                             config('activity-log-discord.events.custom'));
        
        $icon = $eventConfig['icon'] ?? '📝';
        $eventName = str_replace(['.', '_'], ' ', $activityLog->event_type);
        $eventName = ucwords($eventName);
        
        return "{$icon} {$eventName}";
    }

    protected function formatCauser($causer): string
    {
        if (method_exists($causer, 'getDisplayName')) {
            return $causer->getDisplayName();
        }
        
        if (isset($causer->name)) {
            return $causer->name;
        }
        
        if (isset($causer->email)) {
            return $causer->email;
        }
        
        return class_basename($causer) . " #{$causer->id}";
    }

    protected function formatSubject($subject): string
    {
        if (method_exists($subject, 'getDisplayName')) {
            return $subject->getDisplayName();
        }
        
        if (isset($subject->title)) {
            return $subject->title;
        }
        
        if (isset($subject->name)) {
            return $subject->name;
        }
        
        return class_basename($subject) . " #{$subject->id}";
    }

    protected function formatProperties(array $properties): string
    {
        $formatted = [];
        
        foreach ($properties as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT);
            }
            
            $formatted[] = "**{$key}**: {$value}";
        }
        
        return implode("\n", array_slice($formatted, 0, 5)); // Limit to 5 properties
    }
}
