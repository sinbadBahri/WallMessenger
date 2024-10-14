<?php

namespace App\Services\FollowUpService;

use App\Models\AlreadySentNumber;
use App\Services\DelayedMessageService\DelayedMessageService;
use App\Services\MessageService\MessageService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Class FollowUpService
 *
 * This service handles the process of sending follow-up messages to users based on chat interactions.
 * It checks for users who need to be followed up with and sends messages accordingly.
 *
 * The class integrates with external services to manage messages and ensure that delayed follow-up
 * messages are sent, avoiding duplicate sends.
 *
 * @property MessageService $messageService  Service to handle message operations (reading, sending).
 * @property string $followUpMessage  The default follow-up message sent to users.
 * @property DelayedMessageService $delayedMessageService  Service to handle delayed message delivery.
 */
class FollowUpService
{
    private MessageService $messageService;
    private string $followUpMessage;
    private DelayedMessageService $delayedMessageService;

    public function __construct(
        MessageService $messageService, DelayedMessageService  $delayedMessageService
    )
    {
        $this->messageService        = $messageService;
        $this->delayedMessageService = $delayedMessageService;
        $this->setFollowUpMessage();
    }

    /**
     * Processes follow-up messages for users based on their chat interactions.
     *
     * This method retrieves the current chats, checks for any users who need follow-ups,
     * and sends follow-up messages accordingly. It performs the following steps:
     *
     * 1. Retrieves the list of chats using the `getChats()` method.
     * 2. If the chat retrieval fails, it returns an error message.
     * 3. Extracts chat IDs from the response and converts them into mobile numbers.
     * 4. Identifies users who require follow-up messages based on their recent interactions.
     * 5. Sends the follow-up messages to the identified users.
     *
     * @return array An associative array containing the success status and a message:
     *               - 'success'  (bool) : Indicates if the process was successful.
     *               - 'message' (string): A message providing additional information about the outcome.
     *
     * @throws ConnectionException
     */
    public function processFollowUps(): array
    {
        $chatsResponse = $this->getChats();
        if ($chatsResponse === null) {
            return ['success' => false, 'message' => 'Failed to check chats'];
        }

        $chatIds = $this->extractChatIds($chatsResponse);
        $numbers = $this->convertChatIdsToNumbers($chatIds);
        $mustFollowUpUsers = $this->getUsersToFollowUp($numbers, $chatIds);

        return $this->sendFollowUpMessages($mustFollowUpUsers);
    }

    /**
     * Retrieves the current chats from the external chat service API.
     *
     * This method sends a POST request to the chat service endpoint, including
     * necessary headers to specify the expected response format. If the request
     * is successful, it returns the response data as an associative array;
     * otherwise, it returns null to indicate a failure in retrieving the chat data.
     *
     * @return array|null An associative array containing chat data if the
     * request is successful; otherwise, null.
     *
     * @throws ConnectionException
     */
    private function getChats(): ?array
    {
        $response = Http::withHeaders([
            'accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://api.wallmessage.com/api/getChats/?token=5f56c5f0c7514b0382128df609dcdcd2', []);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Extracts unique chat IDs from the provided chats response array.
     *
     * @param array $chatsResponse An associative array containing chat data with a 'value' key
     * that holds chat information.
     *
     * @return array An array of unique chat IDs extracted from the response.
     */
    private function extractChatIds(array $chatsResponse): array
    {
        return array_column($chatsResponse['value'], 'chatId', 'chatId');
    }

    /**
     * Converts chat IDs to mobile numbers by formatting them appropriately.
     *
     * @param array $chatIds An array of chat IDs that need to be converted to mobile numbers.
     *
     * @return array An array of formatted mobile numbers derived from the provided chat IDs.
     */
    private function convertChatIdsToNumbers(array $chatIds): array
    {
        return array_map(fn($chatId) => str_replace(
            '98', '0', str_replace('@c.us', '', $chatId)), $chatIds
        );
    }

    /**
     * Identifies users who require follow-up messages based on their recent chat interactions.
     *
     * This method checks the specified mobile numbers against their corresponding chat IDs to determine if
     * any users have sent a specific message (in this case, "1").
     *
     * It utilizes the `readMessages()` method from the `MessageService` to fetch the last few messages for each chat.
     *
     * If a user has sent the specified message, their mobile number is added to the list of users requiring follow-up.
     *
     * To avoid exceeding rate limits, the method includes a delay between requests.
     *
     * @param array $numbers An array of mobile numbers for which to check recent chat messages.
     * @param array $chatIds An array of corresponding chat IDs for the mobile numbers being checked.
     *
     * @return array An array of mobile numbers representing users who need to be followed up.
     */
    private function getUsersToFollowUp(array $numbers, array $chatIds): array
    {
        $mustFollowUpUsers = [];

        foreach ($numbers as $index => $mobileNumber) {
            sleep(11);
            $chatId = $chatIds[$index];
            $messagesResponse = $this->messageService->readMessages($chatId, $mobileNumber, total: 5);
            if ($messagesResponse && $messagesResponse['isSuccess']) {
                foreach ($messagesResponse['value'] as $messageData) {
                    if ($messageData['message'] === "1") {
                        $mustFollowUpUsers[] = $mobileNumber;
                        break; # Stop checking once we found "1"
                    }
                }
            }
        }

        return $mustFollowUpUsers;
    }

    /**
     * Sends follow-up messages to users identified for follow-up.
     *
     * It performs the following steps:
     *
     * 1. Filters out any users who have already received follow-up messages
     *    using the `removeAlreadySentFromFollowUp()` method.
     * 2. Checks if there are any users left to follow up with:
     *    - If no users are left, logs this information and returns a success
     *      message indicating that there are no follow-ups needed.
     * 3. Iterates through the list of users who need follow-up messages:
     *    - Sends each user a follow-up message using the `sendMessage()` method
     *      from the `MessageService`.
     *    - Invokes the `sendDelayedMessages()` method from the
     *      `DelayedMessageService` for handling any delayed messaging needs.
     *    - Logs the outcome of each message send attempt, noting success or error.
     * 4. Saves the numbers of users who successfully received follow-up messages
     *    using the `saveSentNumbers()` method.
     *
     * @param array $mustFollowUpUsers An array of mobile numbers representing users to send follow-up messages to.
     *
     * @return array An associative array indicating the success of the operation  and a message detailing the outcome.
     */
    private function sendFollowUpMessages(array $mustFollowUpUsers): array
    {

        $mustFollowUpUsers = $this->removeAlreadySentFromFollowUp($mustFollowUpUsers);
        if (empty($mustFollowUpUsers)) {
            Log::info("No users to follow up with.");
            return ['success' => true, 'message' => 'Chats have been checked. No users to follow up with.'];
        }

        $sentNumbers = [];

        foreach ($mustFollowUpUsers as $mobileNumber) {
            sleep(11); # Avoiding too many requests error
            $response = $this->messageService->sendMessage($mobileNumber, $this->followUpMessage);

            $this->delayedMessageService->sendDelayedMessages($mobileNumber);

            if ($response['success']) {
                Log::info("Message sent to $mobileNumber successfully.");
                $sentNumbers[] = $mobileNumber;
            } else {
                Log::error("Failed to send message to $mobileNumber: " . $response['error']);
            }
        }

        $this->saveSentNumbers($sentNumbers);

        return ['success' => true, 'message' => 'Follow up messages sent.'];
    }

    /**
     * Checks if a follow-up message has already been sent to a specific user.
     *
     * @param string $mobileNumber The mobile number to check for previous follow-up messages.
     * @return bool
     */
    private function hasAlreadySent(string $mobileNumber): bool
    {
        return AlreadySentNumber::where('mobile_number', $mobileNumber)->exists();
    }

    /**
     * Saves the mobile numbers of users to whom follow-up messages have been sent.
     *
     * @param array $sentNumbers Mobile numbers representing users who have successfully received follow-up messages.
     * @return void
     */
    private function saveSentNumbers(array $sentNumbers): void
    {
        foreach ($sentNumbers as $mobileNumber) {
            AlreadySentNumber::create(['mobile_number' => $mobileNumber]);
        }
    }

    /**
     * Removes users from the follow-up list who have already received follow-up messages.
     *
     * This method follows these steps:
     *
     * 1. Accepts an array of mobile numbers that represent users to be followed up.
     * 2. Iterates through each mobile number in the provided array.
     * 3. For each mobile number:
     *    - Checks if a follow-up message has already been sent using the
     *      `hasAlreadySent()` method.
     *    - If the message has been sent:
     *      - Logs an informational message indicating that the user will be skipped.
     *      - Finds the index of the mobile number in the array.
     *      - Removes the mobile number from the array.
     * 4. Returns the modified array of mobile numbers, which excludes those who
     *    have already received follow-up messages.
     *
     * @param array $mustFollowUpUsers An array of mobile numbers representing users who need to be followed up.
     *
     * @return array An array of mobile numbers that have not received follow-up messages.
     */
    private function removeAlreadySentFromFollowUp(array $mustFollowUpUsers): array
    {
        foreach ($mustFollowUpUsers as $phoneNumber) {

            if ($this->hasAlreadySent($phoneNumber)) {
                Log::info("Message to $phoneNumber already sent. Skipping...");

                $key = array_search($phoneNumber, $mustFollowUpUsers);

                if ($key !== false) {
                    // Remove "hi" from the array
                    unset($mustFollowUpUsers[$key]);
                }
            }
        }

        return $mustFollowUpUsers;
    }

    /**
     * Sets the follow-up message to be sent to users.
     *
     * @return void
     */
    private function setFollowUpMessage(): void
    {
        $this->followUpMessage = "
        سلام وقتتون بخیر
        تخفیف های ما :
        تخفیف پاییزه برای همه کالا ها ۲۰٪
        تخفیف ویژه کسانی که تازه ثبت نام کرده اند ۴۰٪
        جوایز ما:
        قرعه کشی و جوایز ۱۰ میلیون ریالی
        ";
    }
}
