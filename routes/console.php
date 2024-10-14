<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\CleanupSentNumbersCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule the 'cleanup:sent-numbers' command to run every minute
Artisan::command('cleanup:sent-numbers', function () {
    // Call the command that will handle the cleanup logic
    $this->call(CleanupSentNumbersCommand::class);
})->purpose('Cleanup sent numbers from the database')->everyMinute();
