<?php

namespace App\Services\MessageService;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

/**
 * Class MessageService
 *
 * Handles the sending and reading of messages through external APIs, specifically the
 * Inboxino API for sending messages and the WallMessage API for reading messages.
 *
 * This service is responsible for constructing the appropriate request data, setting up
 * necessary headers, and handling responses or exceptions that may arise during the
 * API interactions. It aims to encapsulate all messaging-related functionality
 * in a cohesive manner while adhering to SOLID principles.
 *
 * @package App\Services
 * @property Client $client The HTTP client used for making API requests.
 * @property string $apiToken The API token for authenticating requests, retrieved from configuration.
 * @property string $inboxinoUrl The URL endpoint for the Inboxino API used to send messages.
 * @property string $wallMessageUrl The URL endpoint for the WallMessage API used to read messages.
 *
 * @method array sendMessage(string $mobileNumber, string $answerMessage) Sends a message to a specified mobile number via the Inboxino API.
 * @method array|null readMessages(string $chatID, string $mobileNumber) Retrieves messages for a specific chat ID from the WallMessage API.
 * @method array buildMessageRequestData(string $mobileNumber, string $answerMessage) Constructs the request payload for sending a message.
 * @method array getCommonHeaders() Returns an array of common headers used in API requests, including authorization tokens.
 * @method array handleResponse($response, string $successMessage) Processes successful API responses and formats them for return.
 * @method array handleException(\Exception $exception, string $failureMessage) Manages exceptions during API requests and returns error details.
 */
class MessageService
{
    protected $client;
    protected $apiToken;
    protected $inboxinoUrl;
    protected $wallMessageUrl;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->apiToken = config('services.inboxino.api_token'); // Store tokens in config or env
        $this->inboxinoUrl = config('services.inboxino.url');
        $this->wallMessageUrl = config('services.wallmessage.url');
    }

    /**
     * Sends a message using the Inboxino API
     */
    public function sendMessage(string $mobileNumber, string $answerMessage): array
    {
        $requestData = $this->buildMessageRequestData($mobileNumber, $answerMessage);

        try {
            $response = $this->client->post($this->inboxinoUrl, [
                'headers' => $this->getCommonHeaders(),
                'json' => $requestData
            ]);

            return $this->handleResponse($response, 'Message sent successfully.');

        } catch (\Exception $exception) {
            return $this->handleException($exception, 'Failed to send the message.');
        }
    }

    /**
     * Builds the request data for sending a message
     */
    protected function buildMessageRequestData(string $mobileNumber, string $answerMessage): array
    {
        return [
            "messages" => [
                [
                    "message"         => $answerMessage,
                    "message_type"    => "message",
                    "attachment_file" => "",
                ],
            ],
            "recipients" => [$mobileNumber],
            "platforms"  => ["whatsapp"],
            "setting" => [
                "expired_minutes" => "",
            ],
            "with_country_code" => true,
            "country_code" => "+98"
        ];
    }

    /**
     * Reads messages using WallMessage API
     */
    public function readMessages(string $chatID, string $mobileNumber): ?array
    {
        $data = [
            'chatId'     => $chatID,
            'mobile'     => $mobileNumber,
            'total'      => 1,
            'ignoreData' => true,
        ];

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->wallMessageUrl, $data);

            if ($response->successful()) {
                // Trigger delayed messages
                return $response->json();
            } else {
                return null;
            }

        } catch (\Exception $exception) {
            return  $this->handleException($exception, 'Specified message did not match');
        }
    }

    /**
     * Gets common headers for the requests
     */
    protected function getCommonHeaders(): array
    {
        return [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'api-token'     => $this->apiToken,
            'Authorization' => 'Bearer ' . $this->apiToken
        ];
    }

    /**
     * Handles the response from an API request
     */
    protected function handleResponse($response, string $successMessage): array
    {
        return [
            'success' => true,
            'message' => $successMessage,
            'body'    => json_decode($response->getBody(), true),
        ];
    }

    /**
     * Handles exceptions for API requests
     */
    protected function handleException(\Exception $exception, string $failureMessage): array
    {
        return [
            'success' => false,
            'message' => $failureMessage,
            'error'   => $exception->getMessage(),
        ];
    }
}
