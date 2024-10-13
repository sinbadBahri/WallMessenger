<?php

namespace App\Jobs;

use App\Services\MessageService\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class SendDelayedMessage
 *
 * A queued job to send a delayed message to a specified mobile number using the MessageService.
 * This job implements the ShouldQueue interface, allowing it to be processed asynchronously via
 * Laravel's queue system.
 *
 * Traits:
 * - Dispatchable: Allows the job to be dispatched.
 * - InteractsWithQueue: Provides methods to interact with the queue.
 * - Queueable: Marks the job as queueable.
 * - SerializesModels: Serializes Eloquent models for storage in the queue.
 *
 * @property string $mobileNumber The mobile number where the message will be sent.
 * @property string $message      The message content to be sent.
 *
 * @method void __construct(string $mobileNumber, string $message) Create a new job instance with the provided mobile number and message.
 * @method void handle(MessageService $messageService) Executes the job by sending the message using MessageService.
 */
class SendDelayedMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $mobileNumber;
    protected string $message;

    /**
     * Creates a new job instance.
     */
    public function __construct(string $mobileNumber, string $message)
    {
        $this->mobileNumber = $mobileNumber;
        $this->message      = $message;
    }

    /**
     * Executes the job.
     */
    public function handle(MessageService $messageService)
    {
        // Send the delayed message
        $messageService->sendMessage($this->mobileNumber, $this->message);
    }
}
