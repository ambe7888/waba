<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Yantrana\Components\Contact\ContactReminderEngine;

class ProcessContactReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contact-reminders:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due contact reminders and execute notifications or auto-messages';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Processing contact reminders...");

        $engine = app(ContactReminderEngine::class);
        $executedCount = $engine->executeDueReminders();

        $this->info("Executed {$executedCount} due contact reminders.");

        return Command::SUCCESS;
    }
}
