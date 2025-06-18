<?php

namespace teaminfinitydev\ActivityLogDiscord\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use teaminfinitydev\ActivityLogDiscord\Models\ActivityLog;

class DiscordWebhookService
{
    protected Client $client;
    protected ?string $webhookUrl;
    protected string $botName;
    protected ?string $avatarUrl;

    public function __construct(?string $webhookUrl, string $botName = 'Activity Logger', ?string $avatarUrl = null)
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true,
        ]);
        $this->webhookUrl = $webhookUrl;
        $this->botName = $botName;
        $this->avatarUrl = $avatarUrl;
    }

    public function sendActivityLog(ActivityLog $activityLog): bool
    {
        if (!$this->webhookUrl || !config('activity-log-discord.enabled')) {
            Log::info('Discord webhook not configured or disabled', [
                'webhook_configured' => !empty($this->webhookUrl),
                'discord_enabled' => config('activity-log-discord.enabled')
            ]);
            return false;
        }

        $embed = $this->buildEmbed($activityLog);
        
        return $this->sendEmbed($embed, $activityLog);
    }

    public function testWebhook(): array
    {
        if (!$this->webhookUrl) {
            return [
                'success' => false,
                'message' => 'Discord webhook URL not configured',
                'details' => 'Please set DISCORD_WEBHOOK_URL in your environment file'
            ];
        }

        if (!config('activity-log-discord.enabled')) {
            return [
                'success' => false,
                'message' => 'Discord notifications are disabled',
                'details' => 'Please set ACTIVITY_LOG_DISCORD_ENABLED=true in your environment file'
            ];
        }

        $testEmbed = [
            'title' => '🧪 Webhook Test',
            'description' => 'This is a test message to verify your Discord webhook integration is working correctly.',
            'color' => 0x00ff00,
            'timestamp' => now()->toISOString(),
            'fields' => [
                [
                    'name' => 'Application',
                    'value' => config('app.name', 'Laravel Application'),
                    'inline' => true,
                ],
                [
                    'name' => 'Environment',
                    'value' => app()->environment(),
                    'inline' => true,
                ],
                [
                    'name' => 'Test Time',
                    'value' => now()->format('Y-m-d H:i:s T'),
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'Activity Log Discord Package',
            ],
        ];

        try {
            $response = $this->client->post($this->webhookUrl, [
                'json' => [
                    'username' => $this->botName,
                    'avatar_url' => $this->avatarUrl,
                    'embeds' => [$testEmbed],
                ],
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() === 204) {
                return [
                    'success' => true,
                    'message' => 'Test webhook sent successfully!',
                    'details' => 'Check your Discord channel for the test message.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Unexpected response from Discord',
                    'details' => "HTTP Status: {$response->getStatusCode()}"
                ];
            }
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            
            Log::error('Discord webhook test failed', [
                'error' => $errorMessage,
                'status_code' => $statusCode,
                'webhook_url' => $this->maskWebhookUrl($this->webhookUrl),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send test webhook',
                'details' => $this->getErrorDetails($e)
            ];
        } catch (\Exception $e) {
            Log::error('Unexpected error during webhook test', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Unexpected error occurred',
                'details' => $e->getMessage()
            ];
        }
    }

    protected function sendEmbed(array $embed, ?ActivityLog $activityLog = null): bool
    {
        try {
            $response = $this->client->post($this->webhookUrl, [
                'json' => [
                    'username' => $this->botName,
                    'avatar_url' => $this->avatarUrl,
                    'embeds' => [$embed],
                ],
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() === 204) {
                if ($activityLog) {
                    $activityLog->update([
                        'discord_sent' => true,
                        'discord_sent_at' => now(),
                    ]);
                }
                return true;
            }
        } catch (RequestException $e) {
            Log::error('Discord webhook failed', [
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
                'activity_log_id' => $activityLog?->id,
                'webhook_url' => $this->maskWebhookUrl($this->webhookUrl),
            ]);
        } catch (\Exception $e) {
            Log::error('Unexpected error sending Discord webhook', [
                'error' => $e->getMessage(),
                'activity_log_id' => $activityLog?->id,
                'trace' => $e->getTraceAsString()
            ]);
        }

        return false;
    }

    protected function buildEmbed(ActivityLog $activityLog): array
    {
        $eventConfig = config("activity-log-discord.events.{$activityLog->event_type}");
        
        // Fall back to custom event config if specific event not found
        if (!$eventConfig) {
            $eventConfig = config('activity-log-discord.events.custom', [
                'color' => 0x9900ff,
                'icon' => '📝'
            ]);
        }

        $embed = [
            'title' => $this->formatTitle($activityLog),
            'description' => $this->truncateText($activityLog->description, 2048),
            'color' => $eventConfig['color'] ?? 0x9900ff,
            'timestamp' => $activityLog->created_at->toISOString(),
            'fields' => [],
        ];

        // Add causer information
        if ($activityLog->causer) {
            $embed['fields'][] = [
                'name' => 'Performed by',
                'value' => $this->truncateText($this->formatCauser($activityLog->causer), 1024),
                'inline' => true,
            ];
        }

        // Add subject information
        if ($activityLog->subject) {
            $embed['fields'][] = [
                'name' => 'Subject',
                'value' => $this->truncateText($this->formatSubject($activityLog->subject), 1024),
                'inline' => true,
            ];
        }

        // Add properties if available
        if ($activityLog->properties && !empty($activityLog->properties)) {
            $propertiesText = $this->formatProperties($activityLog->properties);
            if (!empty($propertiesText)) {
                $embed['fields'][] = [
                    'name' => 'Details',
                    'value' => $this->truncateText($propertiesText, 1024),
                    'inline' => false,
                ];
            }
        }

        // Add footer with app info
        $embed['footer'] = [
            'text' => config('app.name', 'Laravel App') . ' • ' . now()->format('M j, Y'),
        ];

        return $embed;
    }

    protected function formatTitle(ActivityLog $activityLog): string
    {
        $eventConfig = config("activity-log-discord.events.{$activityLog->event_type}");
        
        if (!$eventConfig) {
            $eventConfig = config('activity-log-discord.events.custom', ['icon' => '📝']);
        }
        
        $icon = $eventConfig['icon'] ?? '📝';
        $eventName = str_replace(['.', '_'], ' ', $activityLog->event_type);
        $eventName = ucwords($eventName);
        
        return $this->truncateText("{$icon} {$eventName}", 256);
    }

    protected function formatCauser($causer): string
    {
        try {
            if (!$causer) {
                return 'System';
            }

            if (method_exists($causer, 'getDisplayName')) {
                return $causer->getDisplayName();
            }
            
            if (isset($causer->name) && isset($causer->email)) {
                return "{$causer->name} ({$causer->email})";
            }
            
            if (isset($causer->name)) {
                return $causer->name;
            }
            
            if (isset($causer->email)) {
                return $causer->email;
            }
            
            return class_basename($causer) . " #{$causer->getKey()}";
        } catch (\Exception $e) {
            return 'Unknown User';
        }
    }

    protected function formatSubject($subject): string
    {
        try {
            if (!$subject) {
                return 'Unknown';
            }

            if (method_exists($subject, 'getDisplayName')) {
                return $subject->getDisplayName();
            }
            
            $nameFields = ['title', 'name', 'label', 'display_name'];
            foreach ($nameFields as $field) {
                if (isset($subject->$field) && !empty($subject->$field)) {
                    return $subject->$field;
                }
            }
            
            return class_basename($subject) . " #{$subject->getKey()}";
        } catch (\Exception $e) {
            return 'Unknown Subject';
        }
    }

    protected function formatProperties(array $properties): string
    {
        $formatted = [];
        $count = 0;
        $maxProperties = 10; // Limit number of properties to prevent message being too long
        
        foreach ($properties as $key => $value) {
            if ($count >= $maxProperties) {
                $formatted[] = "... and " . (count($properties) - $maxProperties) . " more properties";
                break;
            }

            $key = ucwords(str_replace(['_', '-'], ' ', $key));
            
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
                if (strlen($value) > 100) {
                    $value = substr($value, 0, 97) . '...';
                }
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            } elseif (is_null($value)) {
                $value = 'null';
            } else {
                $value = (string) $value;
                if (strlen($value) > 100) {
                    $value = substr($value, 0, 97) . '...';
                }
            }
            
            $formatted[] = "**{$key}**: {$value}";
            $count++;
        }
        
        return implode("\n", $formatted);
    }

    protected function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        
        return substr($text, 0, $maxLength - 3) . '...';
    }

    protected function maskWebhookUrl(?string $url): string
    {
        if (!$url) {
            return 'null';
        }
        
        // Extract the webhook ID from the URL for logging without exposing the token
        if (preg_match('/webhooks\/(\d+)\//', $url, $matches)) {
            return "Discord webhook ID: {$matches[1]}";
        }
        
        return 'Discord webhook URL (masked)';
    }

    protected function getErrorDetails(RequestException $e): string
    {
        $response = $e->getResponse();
        if (!$response) {
            return 'No response from Discord (connection error)';
        }

        $statusCode = $response->getStatusCode();
        
        switch ($statusCode) {
            case 400:
                return 'Bad Request - Invalid webhook data format';
            case 401:
                return 'Unauthorized - Invalid webhook URL or token';
            case 404:
                return 'Not Found - Webhook URL does not exist or has been deleted';
            case 429:
                return 'Rate Limited - Too many requests, please try again later';
            case 500:
            case 502:
            case 503:
            case 504:
                return 'Discord server error - Please try again later';
            default:
                return "HTTP {$statusCode} - " . $e->getMessage();
        }
    }
}