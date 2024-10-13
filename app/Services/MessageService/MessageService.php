<?php

namespace App\Services\MessageService;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

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
     * Send a message using the Inboxino API
     *
     * @param string $mobileNumber
     * @param string $answerMessage
     * @return array
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
     * Build the request data for sending a message
     *
     * @param string $mobileNumber
     * @param string $answerMessage
     * @return array
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
     * Read messages using WallMessage API
     *
     * @param string $chatID
     * @param string $mobileNumber
     * @return array|null
     */
    public function readMessages(string $chatID, string $mobileNumber): ?array
    {
        $data = [
            'chatId' => $chatID,
            'mobile' => $mobileNumber,
            'total' => 1,
            'ignoreData' => true,
        ];

        try {
            $response = $this->client->post($this->wallMessageUrl, [
                'headers' => $this->getCommonHeaders(),
                'json' => $data
            ]);

            return $response->successful() ? $response->json() : null;

        } catch (\Exception $exception) {
            return  $this->handleException($exception, 'Specified message did not match');
        }
    }

    /**
     * Get common headers for the requests
     *
     * @return array
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
     * Handle the response from an API request
     *
     * @param $response
     * @param string $successMessage
     * @return array
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
     * Handle exceptions for API requests
     *
     * @param \Exception $exception
     * @param string $failureMessage
     * @return array
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
