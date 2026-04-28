<?php

namespace App\Http\Controllers;

use App\Services\MarketingChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MarketingChatbotController extends Controller
{
    public function __construct(private readonly MarketingChatbotService $chatbot)
    {
    }

    public function ask(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'message' => 'required|string|max:500',
                'session_id' => 'nullable|string|max:64',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Please type a short message (max 500 characters).',
            ], 422);
        }

        $sessionId = $data['session_id'] ?? Str::random(32);

        try {
            $result = $this->chatbot->ask(
                userMessage: $data['message'],
                sessionId: $sessionId,
                ip: $request->ip(),
            );
        } catch (RuntimeException $e) {
            // Service throws user-safe messages already (the constructor of these
            // exceptions never includes internals — see MarketingChatbotService).
            return response()->json([
                'response' => $e->getMessage(),
                'session_id' => $sessionId,
                'refused' => true,
            ], 200); // 200 so the widget renders the message gracefully
        } catch (\Throwable $e) {
            Log::error('marketing_chatbot.unhandled', [
                'msg' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            return response()->json([
                'response' => "Something went wrong on our side — please use the Talk-to-us form and our team will reply within one working day.",
                'session_id' => $sessionId,
                'refused' => true,
            ], 200);
        }

        return response()->json([
            'response' => $result['response'],
            'session_id' => $sessionId,
            'refused' => $result['refused'],
        ]);
    }
}
