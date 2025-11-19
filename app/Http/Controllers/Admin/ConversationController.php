<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\WhatsappUser;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConversationController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Get messages for a conversation with pagination.
     */
    public function messages(Conversation $conversation, Request $request): JsonResponse
    {
        $perPage = 20;
        $beforeId = $request->input('before_id');

        $messagesQuery = $conversation->messages()
            ->when($beforeId, function ($query, $beforeId) {
                $query->where('id', '<', $beforeId);
            })
            ->orderBy('id', 'desc')
            ->limit($perPage);

        $messages = $messagesQuery->get()->reverse()->values();

        return response()->json([
            'messages' => $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'whatsapp_message_id' => $message->whatsapp_message_id,
                    'direction' => $message->direction,
                    'type' => $message->type,
                    'content' => $message->content,
                    'media_url' => $message->media_url,
                    'media_mime_type' => $message->media_mime_type,
                    'caption' => $message->caption,
                    'status' => $message->status,
                    'metadata' => $message->metadata,
                    'sent_at' => $message->sent_at,
                    'delivered_at' => $message->delivered_at,
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at->toISOString(),
                ];
            }),
            'has_more' => $messages->count() === $perPage,
            'oldest_id' => $messages->first()?->id,
        ]);
    }

    /**
     * Send a message to a WhatsApp user.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:4096',
        ]);

        $whatsappUser = $conversation->whatsappUser;

        $message = $this->whatsAppService->sendTextMessage(
            $whatsappUser,
            $request->message,
            $conversation
        );

        if (!$message) {
            return response()->json([
                'error' => 'Failed to send message',
            ], 500);
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'whatsapp_message_id' => $message->whatsapp_message_id,
                'direction' => $message->direction,
                'type' => $message->type,
                'content' => $message->content,
                'status' => $message->status,
                'sent_at' => $message->sent_at,
                'created_at' => $message->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Get or create an active conversation for a user.
     */
    public function getOrCreate(WhatsappUser $whatsappUser): JsonResponse
    {
        $conversation = $whatsappUser->getOrCreateActiveConversation();

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'last_message_at' => $conversation->last_message_at,
            ],
        ]);
    }

    /**
     * Close a conversation.
     */
    public function close(Conversation $conversation): JsonResponse
    {
        $conversation->update(['status' => 'closed']);

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
            ],
        ]);
    }
}
