<?php

namespace App\Services\SentNumberCleanupService;

use App\Models\AlreadySentNumber;
use Illuminate\Support\Carbon;

/**
 * Class SentNumberCleanupService
 *
 * This service is responsible for cleaning up old entries from the
 * AlreadySentNumber database table. It identifies entries that are
 * older than a specified threshold and deletes them to available those
 * numbers again for sending FollowUp Messages.
 */
class SentNumberCleanupService
{
    /**
     * Removes old entries from the AlreadySentNumber table.
     *
     * @return void
     */
    public function cleanupOldEntries(): void
    {
        $threshold = Carbon::now()->subMinutes(3);

        AlreadySentNumber::where('created_at', '<', $threshold)->delete();
    }
}
