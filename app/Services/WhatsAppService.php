<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\WhatsappUser;
use Illuminate\Support\Facades\Log;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class WhatsAppService
{
    protected WhatsAppCloudApi $whatsAppApi;

    public function __construct()
    {
        $this->whatsAppApi = new WhatsAppCloudApi([
            'from_phone_number_id' => config('whatsapp.from_phone_number_id'),
            'access_token' => config('whatsapp.access_token'),
        ]);
    }

    /**
     * Get or create a WhatsApp user by phone number.
     */
    public function getOrCreateUser(string $phoneNumber, ?string $name = null): WhatsappUser
    {
        $user = WhatsappUser::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'name' => $name,
                'is_active' => true,
                'last_interaction_at' => now(),
            ]
        );

        // Assign default role if user is new and has no roles
        if ($user->wasRecentlyCreated && !$user->hasAnyRole()) {
            $user->assignRole(config('whatsapp.default_user_role', 'guest'));
        }

        return $user;
    }

    /**
     * Process an incoming message from WhatsApp webhook.
     */
    public function processIncomingMessage(array $messageData): ?Message
    {
        try {
            $phoneNumber = $messageData['from'];
            $whatsappMessageId = $messageData['id'];
            $timestamp = $messageData['timestamp'];

            // Get or create user
            $user = $this->getOrCreateUser($phoneNumber, $messageData['profile_name'] ?? null);

            // Update last interaction
            $user->update(['last_interaction_at' => now()]);

            // Get or create active conversation
            $conversation = $user->getOrCreateActiveConversation();

            // Parse message content based on type
            $messageType = $messageData['type'];
            $content = $this->extractMessageContent($messageData, $messageType);

            // Create message record
            $message = $conversation->messages()->create([
                'whatsapp_message_id' => $whatsappMessageId,
                'direction' => 'inbound',
                'type' => $messageType,
                'content' => $content['text'] ?? null,
                'media_url' => $content['media_url'] ?? null,
                'media_mime_type' => $content['media_mime_type'] ?? null,
                'caption' => $content['caption'] ?? null,
                'status' => 'sent',
                'metadata' => $content['metadata'] ?? null,
                'sent_at' => date('Y-m-d H:i:s', $timestamp),
            ]);

            // Update conversation last message time
            $conversation->update(['last_message_at' => now()]);

            return $message;
        } catch (\Exception $e) {
            Log::error('Error processing incoming WhatsApp message', [
                'error' => $e->getMessage(),
                'message_data' => $messageData,
            ]);

            return null;
        }
    }

    /**
     * Extract message content based on message type.
     */
    protected function extractMessageContent(array $messageData, string $type): array
    {
        $content = [];

        switch ($type) {
            case 'text':
                $content['text'] = $messageData['text']['body'] ?? null;
                break;

            case 'image':
            case 'video':
            case 'audio':
            case 'document':
            case 'sticker':
                $media = $messageData[$type] ?? [];
                $content['media_url'] = $media['id'] ?? null;
                $content['media_mime_type'] = $media['mime_type'] ?? null;
                $content['caption'] = $media['caption'] ?? null;
                break;

            case 'location':
                $location = $messageData['location'] ?? [];
                $content['text'] = "{$location['name']} - {$location['address']}";
                $content['metadata'] = [
                    'latitude' => $location['latitude'] ?? null,
                    'longitude' => $location['longitude'] ?? null,
                    'name' => $location['name'] ?? null,
                    'address' => $location['address'] ?? null,
                ];
                break;

            case 'contact':
                $contacts = $messageData['contacts'] ?? [];
                $content['text'] = 'Contact shared';
                $content['metadata'] = $contacts;
                break;

            case 'interactive':
                $interactive = $messageData['interactive'] ?? [];
                $content['text'] = $interactive['button_reply']['title'] ?? $interactive['list_reply']['title'] ?? null;
                $content['metadata'] = $interactive;
                break;

            case 'reaction':
                $reaction = $messageData['reaction'] ?? [];
                $content['text'] = $reaction['emoji'] ?? null;
                $content['metadata'] = $reaction;
                break;

            default:
                $content['metadata'] = $messageData;
                break;
        }

        return $content;
    }

    /**
     * Send a text message to a WhatsApp user.
     */
    public function sendTextMessage(WhatsappUser $user, string $message, ?Conversation $conversation = null): ?Message
    {
        try {
            $response = $this->whatsAppApi->sendTextMessage(
                $user->phone_number,
                $message,
                false
            );

            $whatsappMessageId = $response->decodedBody()['messages'][0]['id'] ?? null;

            // Get or create conversation
            if (!$conversation) {
                $conversation = $user->getOrCreateActiveConversation();
            }

            // Store message in database
            $messageRecord = $conversation->messages()->create([
                'whatsapp_message_id' => $whatsappMessageId,
                'direction' => 'outbound',
                'type' => 'text',
                'content' => $message,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            // Update conversation
            $conversation->update(['last_message_at' => now()]);

            return $messageRecord;
        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp text message', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'phone' => $user->phone_number,
            ]);

            return null;
        }
    }

    /**
     * Send a template message to a WhatsApp user.
     */
    public function sendTemplateMessage(
        WhatsappUser $user,
        string $templateName,
        string $languageCode = 'en_US',
        array $parameters = [],
        ?Conversation $conversation = null
    ): ?Message {
        try {
            $response = $this->whatsAppApi->sendTemplate(
                $user->phone_number,
                $templateName,
                $languageCode,
                $parameters
            );

            $whatsappMessageId = $response->decodedBody()['messages'][0]['id'] ?? null;

            // Get or create conversation
            if (!$conversation) {
                $conversation = $user->getOrCreateActiveConversation();
            }

            // Store message in database
            $messageRecord = $conversation->messages()->create([
                'whatsapp_message_id' => $whatsappMessageId,
                'direction' => 'outbound',
                'type' => 'template',
                'content' => "Template: {$templateName}",
                'status' => 'sent',
                'metadata' => [
                    'template_name' => $templateName,
                    'language_code' => $languageCode,
                    'parameters' => $parameters,
                ],
                'sent_at' => now(),
            ]);

            // Update conversation
            $conversation->update(['last_message_at' => now()]);

            return $messageRecord;
        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp template message', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'template' => $templateName,
            ]);

            return null;
        }
    }

    /**
     * Process message status update from webhook.
     */
    public function processStatusUpdate(array $statusData): void
    {
        try {
            $whatsappMessageId = $statusData['id'];
            $status = $statusData['status'];

            $message = Message::where('whatsapp_message_id', $whatsappMessageId)->first();

            if (!$message) {
                return;
            }

            $updateData = ['status' => $status];

            switch ($status) {
                case 'delivered':
                    $updateData['delivered_at'] = now();
                    break;
                case 'read':
                    $updateData['read_at'] = now();
                    break;
            }

            $message->update($updateData);
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp status update', [
                'error' => $e->getMessage(),
                'status_data' => $statusData,
            ]);
        }
    }

    /**
     * Mark a conversation as read.
     */
    public function markAsRead(string $whatsappMessageId): void
    {
        try {
            $this->whatsAppApi->markMessageAsRead($whatsappMessageId);
        } catch (\Exception $e) {
            Log::error('Error marking WhatsApp message as read', [
                'error' => $e->getMessage(),
                'message_id' => $whatsappMessageId,
            ]);
        }
    }
}
