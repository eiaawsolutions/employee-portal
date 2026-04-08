<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\{AccountingAuditTrail, AiChatMessage, AiChatSession, AiInvoiceScan, Bill, BillItem, TaxCode, Vendor};
use App\Services\AiAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AiAccountingController extends Controller
{
    // ── Invoice Scanner ──────────────────────────────────────────
    public function invoiceScanner(Request $request)
    {
        if (!Auth::user()->canManageAccounting()) abort(403);
        $company = $request->get('company') ?? Auth::user()->employee?->company;
        $scans = AiInvoiceScan::when($company, fn($q) => $q->where('company', $company))
            ->latest()
            ->paginate(25);
        $companies = \App\Models\Company::orderBy('name')->pluck('name', 'name');

        $settings  = \App\Models\Accounting\AccountingSetting::resolveForAi($company);
        $hasApiKey = ($settings?->hasAiApiKey()) || (bool) config('services.openai.api_key');

        return view('accounting.ai.invoice-scanner', compact('scans', 'company', 'companies', 'hasApiKey'));
    }

    public function uploadInvoice(Request $request)
    {
        if (!Auth::user()->canManageAccounting()) abort(403);

        $company  = $request->get('company') ?? Auth::user()->employee?->company;
        $settings = \App\Models\Accounting\AccountingSetting::resolveForAi($company);
        if (!$settings?->hasAiApiKey() && !config('services.openai.api_key')) {
            return back()->with('error', 'No AI API key configured. Go to Accounting Settings to add your OpenAI API key.');
        }

        $ai = new AiAccountingService($company);

        $request->validate([
            'company'  => 'nullable|string|max:255',
            'invoice'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240|valid_file_content',
        ]);

        $file = $request->file('invoice');
        $path = $file->store('accounting/invoice-scans', 'local');

        $scan = AiInvoiceScan::create([
            'company'    => $request->get('company'),
            'file_name'  => $file->getClientOriginalName(),
            'file_type'  => $file->getClientMimeType(),
            'file_path'  => $path,
            'status'     => 'processing',
            'created_by' => Auth::id(),
        ]);

        try {
            $extracted = $ai->extractInvoiceData($scan);

            if (empty($extracted)) {
                $errorDetail = $scan->fresh()->error_message ?? 'Unknown error';
                return back()->with('error', 'AI extraction failed: ' . $errorDetail);
            }
        } catch (\Exception $e) {
            $scan->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return back()->with('error', 'AI extraction failed: ' . $e->getMessage());
        }

        return redirect()->route('accounting.ai.review-scan', $scan)
            ->with('success', 'Invoice scanned and data extracted. Please review.');
    }

    public function reviewScan(AiInvoiceScan $scan)
    {
        if (!Auth::user()->canManageAccounting()) abort(403);
        $vendors  = Vendor::where('is_active', true)->orderBy('name')->get();
        $taxCodes = TaxCode::where('is_active', true)->orderBy('code')->get();
        return view('accounting.ai.review-scan', compact('scan', 'vendors', 'taxCodes'));
    }

    public function scanFile(AiInvoiceScan $scan)
    {
        if (!Auth::user()->canManageAccounting()) abort(403);
        $path = Storage::disk('local')->path($scan->file_path);
        if (!file_exists($path)) abort(404);
        return response()->file($path, ['Content-Type' => $scan->file_type ?? mime_content_type($path)]);
    }

    public function confirmScan(Request $request, AiInvoiceScan $scan)
    {
        if (!Auth::user()->canManageAccounting()) abort(403);

        $data = $request->validate([
            'vendor_id'             => 'required|exists:acc_vendors,id',
            'date'                  => 'required|date',
            'due_date'              => 'required|date|after_or_equal:date',
            'vendor_bill_number'    => 'nullable|string|max:255',
            'items'                 => 'required|array|min:1',
            'items.*.description'   => 'required|string|max:500',
            'items.*.quantity'      => 'required|numeric|min:0.0001',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.tax_code_id'   => 'nullable|exists:acc_tax_codes,id',
        ]);

        return DB::transaction(function () use ($data, $scan) {
            $settings = \App\Models\Accounting\AccountingSetting::where('company', $scan->company)->first();
            $billNumber = $settings
                ? $settings->getNextNumber('bill')
                : 'BILL-' . str_pad(Bill::count() + 1, 6, '0', STR_PAD_LEFT);

            $bill = Bill::create([
                'company'            => $scan->company,
                'vendor_id'          => $data['vendor_id'],
                'bill_number'        => $billNumber,
                'vendor_bill_number' => $data['vendor_bill_number'] ?? null,
                'date'               => $data['date'],
                'due_date'           => $data['due_date'],
                'status'             => 'draft',
                'created_by'         => Auth::id(),
            ]);

            foreach ($data['items'] as $i => $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $taxCode = isset($item['tax_code_id']) ? TaxCode::find($item['tax_code_id']) : null;
                $taxAmount = $taxCode ? $lineTotal * ($taxCode->rate / 100) : 0;

                BillItem::create([
                    'bill_id'     => $bill->id,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'tax_code_id' => $item['tax_code_id'] ?? null,
                    'tax_amount'  => round($taxAmount, 2),
                    'line_total'  => round($lineTotal, 2),
                    'sort_order'  => $i,
                ]);
            }

            $bill->recalculateTotals();

            $scan->update([
                'status'      => 'reviewed',
                'bill_id'     => $bill->id,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            AccountingAuditTrail::log('create', $bill, 'Created from AI invoice scan #' . $scan->id);

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', "Bill {$bill->bill_number} created from scanned invoice.");
        });
    }

    public function destroyScan(AiInvoiceScan $scan)
    {
        if (!Auth::user()->canManageAccounting()) abort(403);
        if ($scan->file_path) {
            Storage::disk('local')->delete($scan->file_path);
        }
        $scan->delete();
        return back()->with('success', 'Scan record deleted.');
    }

    // ── AI Chatbot ───────────────────────────────────────────────
    public function chatbot(Request $request)
    {
        if (!Auth::user()->canUseAiChat()) abort(403);
        $company = $request->get('company');

        $sessions = AiChatSession::where('user_id', Auth::id())
            ->when($company, fn($q) => $q->where('company', $company))
            ->latest()
            ->take(20)
            ->get();

        $companies = \App\Models\Company::orderBy('name')->pluck('name', 'name');
        return view('accounting.ai.chatbot', compact('sessions', 'company', 'companies'));
    }

    public function chatSend(Request $request)
    {
        if (!Auth::user()->canUseAiChat()) abort(403);

        $data = $request->validate([
            'company'    => 'nullable|string|max:255',
            'session_id' => 'nullable|exists:acc_ai_chat_sessions,id',
            'message'    => 'required|string|max:2000',
        ]);

        $company = $data['company'] ?? Auth::user()->employee?->company;
        $ai = new AiAccountingService($company);

        if (!$data['session_id']) {
            $session = AiChatSession::create([
                'company' => $data['company'],
                'user_id' => Auth::id(),
                'title'   => \Illuminate\Support\Str::limit($data['message'], 60),
            ]);
        } else {
            $session = AiChatSession::findOrFail($data['session_id']);
            if ($session->user_id !== Auth::id()) abort(403);
        }

        try {
            $response = $ai->chat($session, $data['message']);
        } catch (\Exception $e) {
            $response = 'Sorry, I encountered an error processing your request. Please try again.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'session_id' => $session->id,
                'message'    => $response,
            ]);
        }

        return redirect()->route('accounting.ai.chatbot', [
            'company' => $data['company'],
            'session' => $session->id,
        ]);
    }

    public function chatSession(AiChatSession $session)
    {
        if (!Auth::user()->canUseAiChat()) abort(403);
        if ($session->user_id !== Auth::id()) abort(403);

        $messages = AiChatMessage::where('session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        $sessions = AiChatSession::where('user_id', Auth::id())
            ->latest()
            ->take(20)
            ->get();

        $companies = \App\Models\Company::orderBy('name')->pluck('name', 'name');
        $company   = $session->company;
        $currentSession = $session;

        return view('accounting.ai.chatbot', compact('currentSession', 'messages', 'sessions', 'company', 'companies'));
    }
}
