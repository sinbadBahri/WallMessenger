<?php

namespace App\Http\Controllers;

use App\Services\MessageService\MessageService;
use App\Http\Requests\HandleMessageRequest;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MainController extends Controller
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function index(): InertiaResponse
    {
        return Inertia::render('Messenger/Home');
    }

    public function formPage(): InertiaResponse
    {
        return Inertia::render('Messenger/Form');
    }

    public function handleMessage(HandleMessageRequest $request): JsonResponse
    {
        $mobileNumber = $request->getMobileNumber();
        $chatID = $this->generateChatID($mobileNumber);

        $responseData = $this->messageService->readMessages($chatID, $mobileNumber);

        if ($this->isMessageValid($responseData, $request->input('specificMessage'))) {
            return $this->sendReply($mobileNumber, $request->input('answerMessage'));
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to read messages or specific message did not match.',
        ], 400);
    }

    private function generateChatID(string $mobileNumber): string
    {
        return '98' . substr($mobileNumber, 1) . '@c.us';
    }

    private function isMessageValid(array $responseData, string $specificMessage): bool
    {
        return $responseData && $responseData['isSuccess'] && !empty($responseData['value'])
            && $responseData['value'][0]['message'] === $specificMessage;
    }

    private function sendReply(string $mobileNumber, string $answerMessage): JsonResponse
    {
        $sendMessageResponse = $this->messageService->sendMessage($mobileNumber, $answerMessage);

        if ($sendMessageResponse['success']) {
            return response()->json($sendMessageResponse);
        }

        return response()->json($sendMessageResponse, 400);
    }
}
