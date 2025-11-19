<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Verify webhook (GET request from WhatsApp).
     */
    public function verify(Request $request): JsonResponse|string
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('whatsapp.webhook_verify_token')) {
            Log::info('WhatsApp webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response()->json(['error' => 'Forbidden'], 403);
    }

    /**
     * Handle incoming webhook events (POST request from WhatsApp).
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $body = $request->all();

            Log::info('WhatsApp webhook received', ['body' => $body]);

            // Validate the webhook signature (optional but recommended)
            if (config('whatsapp.app_secret')) {
                if (!$this->validateSignature($request)) {
                    Log::warning('WhatsApp webhook signature validation failed');
                    return response()->json(['error' => 'Invalid signature'], 403);
                }
            }

            // Process webhook data
            if (isset($body['entry'])) {
                foreach ($body['entry'] as $entry) {
                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            $this->processChange($change);
                        }
                    }
                }
            }

            return response()->json(['status' => 'ok'], 200);
        } catch (\Exception $e) {
            Log::error('Error handling WhatsApp webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Process a single change from the webhook.
     */
    protected function processChange(array $change): void
    {
        $value = $change['value'] ?? [];

        // Process messages
        if (isset($value['messages'])) {
            foreach ($value['messages'] as $message) {
                $this->processMessage($message, $value);
            }
        }

        // Process message status updates
        if (isset($value['statuses'])) {
            foreach ($value['statuses'] as $status) {
                $this->whatsAppService->processStatusUpdate($status);
            }
        }
    }

    /**
     * Process an incoming message.
     */
    protected function processMessage(array $message, array $value): void
    {
        try {
            // Add profile name from contacts if available
            $contacts = $value['contacts'] ?? [];
            if (!empty($contacts)) {
                $message['profile_name'] = $contacts[0]['profile']['name'] ?? null;
            }

            // Process the message
            $messageRecord = $this->whatsAppService->processIncomingMessage($message);

            if ($messageRecord) {
                // Mark message as read
                $this->whatsAppService->markAsRead($message['id']);

                // Here you can trigger your chatbot logic
                // For example: dispatch a job to process the message and generate a response
                // ProcessChatbotMessage::dispatch($messageRecord);
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);
        }
    }

    /**
     * Validate the webhook signature.
     */
    protected function validateSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            config('whatsapp.app_secret')
        );

        return hash_equals($expectedSignature, $signature);
    }
}
