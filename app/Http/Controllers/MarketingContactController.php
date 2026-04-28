<?php

namespace App\Http\Controllers;

use App\Services\MarketingLeadNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MarketingContactController extends Controller
{
    public function __construct(private readonly MarketingLeadNotifier $notifier)
    {
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:120',
                'email' => 'required|email:rfc|max:191',
                'phone' => 'nullable|string|max:40',
                'company' => 'nullable|string|max:191',
                'message' => 'required|string|max:2000',
                'source' => 'nullable|string|in:chatbot,landing-form,faq-form,voice-agent',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Please check the required fields and try again.',
                'fields' => $e->errors(),
            ], 422);
        }

        $source = $data['source'] ?? 'landing-form';
        $now = now();

        // 1. ALWAYS save the row first. Persistence is non-negotiable.
        try {
            $id = DB::table('marketing_contacts')->insertGetId([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'message' => $data['message'],
                'source' => $source,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'email_attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            // Postgres down or schema drift — this is a P0. The lead is NOT
            // saved here, so the user-facing fallback must give them a way
            // to reach us (the sales email + the original message they typed
            // so they can paste-and-send).
            Log::critical('marketing_contact.insert_failed', [
                'msg' => $e->getMessage(),
                'email' => $data['email'],
            ]);
            $salesEmail = config('eiaaw.sales_email', 'sales@eiaawsolutions.com');
            return response()->json([
                'error' => "We couldn't save your message. Please email {$salesEmail} directly — we read every one.",
                'fallback_sales_email' => $salesEmail,
            ], 500);
        }

        // 2. Try to email sales. The row is already saved, so even if this
        //    fails the lead is recoverable via the cron retry sweep.
        $emailSent = $this->notifier->send($id);

        // 3. ALWAYS return success to the user — their message reached us.
        //    Whether the email already landed or will land via retry sweep,
        //    a real human will reply. We do NOT show "email failed" to the
        //    visitor because their job (sending us the message) is done.
        return response()->json([
            'ok' => true,
            'id' => $id,
            'message' => "Thanks — we'll reply within one working day.",
            // Internal flag for the widget; not user-facing copy.
            '_email_sent' => $emailSent,
        ]);
    }
}
