<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Services\AiGateway;
use App\Services\AiRetrievalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * AiAssistantController — thin HTTP layer over AiGateway.
 *
 * Read-only v1: single-turn queries, no tool execution, no conversation
 * threading beyond what AiGateway persists. Rate-limited 15/min/user at
 * the route level. Plan-gated to ai.basic (Starter and up).
 *
 * JSON API for the drawer UI: POST {prompt} → 200 {answer, meta} or
 * 429/403 with an upgrade/budget message the drawer surfaces inline.
 */
class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiGateway $gateway,
        private readonly AiRetrievalService $retrieval,
    ) {}

    public function ask(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'prompt' => ['required', 'string', 'min:1', 'max:8000'],
            'complexity' => ['nullable', 'in:routine,complex'],
        ])->validate();

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        if (!$tenant) {
            return response()->json(['error' => 'no_tenant_context'], 403);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        // Plan gating: ai.basic is on Starter+; ai.advanced gates 'complex' mode.
        if (!$tenant->hasFeature('ai.basic')) {
            return response()->json([
                'error' => 'upgrade_required',
                'message' => 'AI assistant not available on your plan.',
            ], 403);
        }

        $complexity = $data['complexity'] ?? 'routine';
        if ($complexity === 'complex' && !$tenant->hasFeature('ai.advanced')) {
            $complexity = 'routine';  // silently downgrade; drawer tells user why
        }

        // Retrieval — build role-filtered context BEFORE prompt assembly so
        // the model can only answer from rows the user is authorised to see.
        $retrievalContext = $this->retrieval->contextFor($user, $data['prompt']);

        try {
            $result = $this->gateway->ask($tenant, $user, $data['prompt'], $complexity, $retrievalContext);
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            $status = str_contains($message, 'budget exhausted') ? 429 : 400;
            return response()->json(['error' => 'ai_error', 'message' => $message], $status);
        }

        return response()->json([
            'answer' => $result['answer'],
            'sources' => $result['sources'] ?? [],
            'refused' => $result['refused'] ?? false,
            'refusal_reason' => $result['refusal_reason'] ?? '',
            'meta' => [
                'model' => $result['model'],
                'cost_usd' => $result['cost_usd'],
                'latency_ms' => $result['latency_ms'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
                'monthly_spend_usd' => round($this->gateway->monthlySpendUsd($tenant), 4),
                'monthly_budget_usd' => (float) $tenant->aiBudgetUsd(),
            ],
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $recent = AiConversation::query()
            ->where('user_id', $user->id)
            ->whereIn('role', ['user', 'assistant'])
            ->latest('id')
            ->limit(20)
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json([
            'messages' => $recent->reverse()->values(),
        ]);
    }
}
