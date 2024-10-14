<?php

namespace App\Services\DelayedMessageService;

use App\Jobs\SendDelayedMessage;

/**
 * Class DelayedMessageService
 *
 * Service for handling the scheduling of delayed messages to a specified mobile number.
 * This class provides methods to send predefined messages at specified intervals using
 * queued jobs.
 *
 */
class DelayedMessageService
{
    /**
     * Schedules multiple delayed messages to be sent
     *
     * @param string $mobileNumber The mobile number where the delayed messages will be sent.
     * @return void
     */
    public function sendDelayedMessages(string $mobileNumber): void
    {
        # Schedule "Where are you?" message to be sent after 5 minutes
        SendDelayedMessage::dispatch($mobileNumber, "چیشد پس ؟")
            ->delay(now()->addMinutes(5));

        # Schedule "You forgot your Discount?" message to be sent after 2 hours
        SendDelayedMessage::dispatch($mobileNumber, "تخفیف رو فراموش کردی...")
            ->delay(now()->addHours(2));
    }
}
