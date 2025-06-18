<?php

namespace teaminfinitydev\ActivityLogDiscord\Console\Commands;

use Illuminate\Console\Command;
use teaminfinitydev\ActivityLogDiscord\Services\ActivityLoggerService;
use teaminfinitydev\ActivityLogDiscord\Services\DiscordWebhookService;

class TestWebhookCommand extends Command
{
    protected $signature = 'activity-log:test-webhook 
                          {--detailed : Show detailed configuration information}';

    protected $description = 'Test the Discord webhook integration';

    public function handle(DiscordWebhookService $discordService, ActivityLoggerService $activityLogger): int
    {
        $this->info('🧪 Testing Discord Webhook Integration...');
        $this->newLine();

        // Show configuration details if requested
        if ($this->option('detailed')) {
            $this->showConfiguration();
        }

        // Test webhook directly
        $this->info('Step 1: Testing webhook connection...');
        $result = $discordService->testWebhook();

        if ($result['success']) {
            $this->info("✅ {$result['message']}");
            $this->line("   {$result['details']}");
        } else {
            $this->error("❌ {$result['message']}");
            $this->line("   {$result['details']}");
            return self::FAILURE;
        }

        $this->newLine();

        // Test through activity logger
        $this->info('Step 2: Testing through activity logger...');
        
        try {
            $success = $activityLogger->testWebhook();
            
            if ($success) {
                $this->info('✅ Activity logger test completed successfully!');
                $this->line('   Check your Discord channel for the test message.');
            } else {
                $this->error('❌ Activity logger test failed!');
                $this->line('   Check the logs for more details.');
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Activity logger test threw an exception!');
            $this->line("   Error: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('🎉 All tests passed! Your Discord integration is working correctly.');
        
        return self::SUCCESS;
    }

    protected function showConfiguration(): void
    {
        $this->info('📋 Current Configuration:');
        $this->table(
            ['Setting', 'Value', 'Status'],
            [
                [
                    'Webhook URL',
                    config('activity-log-discord.webhook_url') ? 'Configured' : 'Not set',
                    config('activity-log-discord.webhook_url') ? '✅' : '❌'
                ],
                [
                    'Bot Name',
                    config('activity-log-discord.bot_name', 'Activity Logger'),
                    '✅'
                ],
                [
                    'Discord Enabled',
                    config('activity-log-discord.enabled') ? 'Yes' : 'No',
                    config('activity-log-discord.enabled') ? '✅' : '❌'
                ],
                [
                    'Queue Enabled',
                    config('activity-log-discord.queue_notifications') ? 'Yes' : 'No',
                    '📊'
                ],
                [
                    'Queue Connection',
                    config('activity-log-discord.queue_connection', 'default'),
                    '📊'
                ],
                [
                    'Environment',
                    app()->environment(),
                    '📊'
                ],
                [
                    'Bootup Messages',
                    config('activity-log-discord.send_bootup_message') ? 'Enabled' : 'Disabled',
                    config('activity-log-discord.send_bootup_message') ? '✅' : '❌'
                ],
            ]
        );
        $this->newLine();

        // Show enabled events
        $this->info('🎯 Enabled Events:');
        $events = config('activity-log-discord.events', []);
        $enabledEvents = [];
        
        foreach ($events as $eventType => $config) {
            if ($config['enabled'] ?? true) {
                $enabledEvents[] = [
                    $eventType,
                    $config['icon'] ?? '📝',
                    $this->getColorName($config['color'] ?? 0x9900ff)
                ];
            }
        }

        if (!empty($enabledEvents)) {
            $this->table(['Event Type', 'Icon', 'Color'], $enabledEvents);
        } else {
            $this->warn('No events are currently enabled!');
        }
        
        $this->newLine();
    }

    protected function getColorName(int $color): string
    {
        $colors = [
            0x00ff00 => 'Green',
            0xff0000 => 'Red',
            0xffff00 => 'Yellow',
            0x0099ff => 'Blue',
            0xff9900 => 'Orange',
            0x9900ff => 'Purple',
            0x00ffff => 'Cyan',
        ];

        return $colors[$color] ?? sprintf('#%06X', $color);
    }
}