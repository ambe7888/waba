<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (!getAppSettings('enable_queue_jobs_for_campaigns')) {
            // process webhooks every second if enabled and queue jobs for campaigns is disabled
            if (getAppSettings('enable_wa_webhook_process_using_db')) {
                $schedule->command('whatsapp:webhooks:process')
                    ->everySecond()
                    ->name('process_webhooks_via_cron')
                    ->withoutOverlapping(2) // Prevent overlapping executions
                ;
            }
            // process campaign messages every five seconds if queue jobs for campaigns is disabled
            $schedule->command('whatsapp:campaign:process')
                ->everyFiveSeconds()
                ->name('process_messages_via_cron')
                ->withoutOverlapping(2) // Prevent overlapping executions
            ;
        }

        // Process Drip Campaigns every minute for precise scheduling
        $schedule->command('drip:process')
            ->everyMinute()
            ->name('process_drip_campaigns_via_cron')
            ->withoutOverlapping();

        // Process Contact Reminders every minute
        $schedule->command('contact-reminders:process')
            ->everyMinute()
            ->name('process_contact_reminders_via_cron')
            ->withoutOverlapping();

        // Process SaaS Automations (Subscription expiry & reminders) daily
        $schedule->command('saas:process-automations')
            ->dailyAt('09:00')
            ->name('process_saas_automations')
            ->withoutOverlapping();

        // Reconcile the denormalised last-message / unread columns on
        // contacts. They are maintained live by ContactMessageStatsSync on
        // the message-log model events, but bulk inserts fire no model
        // events, so drift is possible. Recomputes from scratch, so it
        // repairs rather than compounds. Runs at a quiet hour - it is a
        // full pass over every vendor (~10s on current data).
        $schedule->command('contacts:backfill-message-columns')
            ->dailyAt('04:30')
            ->name('reconcile_contact_message_columns')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
