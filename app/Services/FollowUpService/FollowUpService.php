<?php

namespace App\Services\FollowUpService;

use App\Models\AlreadySentNumber;
use App\Services\MessageService\MessageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FollowUpService
{
    private MessageService $messageService;
    private string $followUpMessage;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
        $this->setFollowUpMessage();
    }

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

    private function getChats(): ?array
    {
        $response = Http::withHeaders([
            'accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://api.wallmessage.com/api/getChats/?token=5f56c5f0c7514b0382128df609dcdcd2', []);

        return $response->successful() ? $response->json() : null;
    }

    private function extractChatIds(array $chatsResponse): array
    {
        return array_column($chatsResponse['value'], 'chatId', 'chatId');
    }

    private function convertChatIdsToNumbers(array $chatIds): array
    {
        return array_map(fn($chatId) => str_replace(
            '98', '0', str_replace('@c.us', '', $chatId)), $chatIds
        );
    }

    private function getUsersToFollowUp(array $numbers, array $chatIds): array
    {
        $mustFollowUpUsers = [];

        foreach ($numbers as $index => $mobileNumber) {
            sleep(11); // Avoiding too many requests error
            $chatId = $chatIds[$index];
            $messagesResponse = $this->messageService->readMessages($chatId, $mobileNumber, total: 5);
            if ($messagesResponse && $messagesResponse['isSuccess']) {
                foreach ($messagesResponse['value'] as $messageData) {
                    if ($messageData['message'] === "1") {
                        $mustFollowUpUsers[] = $mobileNumber;
                        break; // Stop checking once we found "1"
                    }
                }
            }
        }

        return $mustFollowUpUsers;
    }

    private function sendFollowUpMessages(array $mustFollowUpUsers): array
    {
        $sentNumbers = [];

        if (empty($mustFollowUpUsers)) {
            Log::info("No users to follow up with.");
            return ['success' => true, 'message' => 'Chats have been checked. No users to follow up with.'];
        }

        foreach ($mustFollowUpUsers as $phoneNumber) {
            # Checks if the number has already been sent a message
            if ($this->hasAlreadySent($phoneNumber)) {
                Log::info("Message to $phoneNumber already sent. Skipping...");
            }
        }

        foreach ($mustFollowUpUsers as $mobileNumber) {
            sleep(11); # Avoiding too many requests error
            $response = $this->messageService->sendMessage($mobileNumber, $this->followUpMessage);

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

    private function hasAlreadySent(string $mobileNumber): bool
    {
        return AlreadySentNumber::where('mobile_number', $mobileNumber)->exists();
    }

    private function saveSentNumbers(array $sentNumbers): void
    {
        foreach ($sentNumbers as $mobileNumber) {
            AlreadySentNumber::create(['mobile_number' => $mobileNumber]);
        }
    }

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
