<?php

namespace App\Http\Controllers;

use App\Services\DelayedMessageService\DelayedMessageService;
use App\Services\MessageService\MessageService;
use App\Http\Requests\HandleMessageRequest;
use Illuminate\Http\JsonResponse;

class MessengerController extends Controller
{
    protected MessageService $messageService;
//    protected DelayedMessageService $delayedMessageService;

    public function __construct(MessageService $messageService,
                                DelayedMessageService  $delayedMessageService)
    {
        $this->messageService = $messageService;
//        $this->delayedMessageService = $delayedMessageService;
    }

    /**
     * Handles the incoming message and respond accordingly.
     *
     * This method processes the incoming message request by extracting the mobile number,
     * generating a chat ID, and reading messages using the MessageService. If the specific
     * message from the incoming request matches a previously received message, it sends
     * an appropriate reply.
     *
     * @param HandleMessageRequest $request The request object containing the mobile number,
     * specific message, and answer message.
     *
     * @return JsonResponse A JSON response indicating the success or failure of the operation.
     * On success, it includes the response from the MessageService.
     * On failure, it provides an error message and a 400 status code.
     */
    public function handleMessage(HandleMessageRequest $request): JsonResponse
    {
        $mobileNumber = $request->getMobileNumber();
        $chatID = $this->generateChatID($mobileNumber);

        $responseData = $this->messageService->readMessages($chatID, $mobileNumber);
        if ($this->isMessageValid($responseData, $request->input('specificMessage'))) {
//            $this->delayedMessageService->sendDelayedMessages($mobileNumber);
            return $this->sendReply($mobileNumber, $request->input('answerMessage'));
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to read messages or specific message did not match.',
        ], 400);
    }

    /**
     * Generates a chat ID based on the provided mobile number.
     *
     * This method transforms the given mobile number into a chat ID format
     * required for interacting with the messaging service. The generated chat
     * ID is prefixed with '98' and appends the mobile number, ensuring it is
     * formatted correctly for use with the messaging platform.
     *
     * @param string $mobileNumber The mobile number from which to generate the chat ID.
     *
     * @return string The formatted chat ID for the specified mobile number.
     */
    private function generateChatID(string $mobileNumber): string
    {
        return '98' . substr($mobileNumber, 1) . '@c.us';
    }

    /**
     * Validates if the received message data matches the expected message.
     *
     * This method checks if the response data from the messaging service
     * indicates a successful retrieval and whether the actual message
     * matches the specified message. It ensures that the message is valid
     * and can be processed further for sending delayed messages.
     *
     * @param array $responseData The response data received from the messaging service.
     * @param string $specificMessage The specific message to compare against the retrieved message.
     *
     * @return bool True if the message is valid and matches the expected message; otherwise, false.
     */
    private function isMessageValid(array $responseData, string $specificMessage): bool
    {
        return $responseData && $responseData['isSuccess'] && !empty($responseData['value'])
            && $responseData['value'][0]['message'] === $specificMessage;
    }

    /**
     * Sends a reply message to the specified mobile number.
     *
     * This method utilizes the MessageService to send a reply message
     * to the provided mobile number.
     * It checks the response from the message sending process and returns
     * a JSON response indicating the success or failure of the operation.
     *
     * In case of failure, an appropriate HTTP status code is returned.
     *
     * @param string $mobileNumber The mobile number to which the reply message will be sent.
     * @param string $answerMessage The message content to be sent as a reply.
     *
     * @return JsonResponse A JSON response indicating the success or failure
     * of the message sending operation.
     */
    private function sendReply(string $mobileNumber, string $answerMessage): JsonResponse
    {
        $sendMessageResponse = $this->messageService->sendMessage($mobileNumber, $answerMessage);

        if ($sendMessageResponse['success']) {
            return response()->json($sendMessageResponse);
        }

        return response()->json($sendMessageResponse, 400);
    }
}
