<?php

namespace App\Services\SentNumberCleanupService;

use App\Models\AlreadySentNumber;
use Illuminate\Support\Carbon;

class SentNumberCleanupService
{
    public function cleanupOldEntries(): void
    {
        $threshold = Carbon::now()->subMinutes(2);

        AlreadySentNumber::where('created_at', '<', $threshold)->delete();
    }
}
