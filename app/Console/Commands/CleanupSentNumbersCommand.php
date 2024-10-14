<?php

namespace App\Console\Commands;

use App\Services\SentNumberCleanupService\SentNumberCleanupService;
use Illuminate\Console\Command;

class CleanupSentNumbersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:sent-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup sent numbers older than 20 minutes';

    private SentNumberCleanupService $cleanupService;

    public function __construct(SentNumberCleanupService $cleanupService)
    {
        parent::__construct();
        $this->cleanupService = $cleanupService;
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->cleanupService->cleanupOldEntries();
        $this->info('Old sent numbers cleaned up successfully.');
    }
}
