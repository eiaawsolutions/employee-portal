<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * MarketingLeadNotifier — sends the sales notification email for a
 * marketing_contacts row, with full failure accounting.
 *
 * Revenue-critical surface: every row is a potential customer. We never
 * want to lose one to a transient SMTP failure. So:
 *   - The DB row is the source of truth (insert before mail).
 *   - On send: increment email_attempts, set emailed_at on success, set
 *     email_failed_at + email_last_error on failure.
 *   - Failures log at ERROR (not WARNING) so existing log-monitoring
 *     surfaces them.
 *   - The marketing:retry-pending-emails command picks up rows with
 *     emailed_at IS NULL and retries up to MAX_ATTEMPTS times.
 *
 * Returns true on success, false on failure (caller can decide what to
 * do with the user-facing response).
 */
class MarketingLeadNotifier
{
    public const MAX_ATTEMPTS = 5;

    public function send(int $contactId): bool
    {
        $row = DB::table('marketing_contacts')->where('id', $contactId)->first();
        if (!$row) {
            Log::error('marketing_lead_notifier.row_missing', ['id' => $contactId]);
            return false;
        }

        // Already sent? Idempotent: skip.
        if (!is_null($row->emailed_at)) {
            return true;
        }

        $attempts = (int) ($row->email_attempts ?? 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            // Hard cap reached — leave the row for human triage.
            return false;
        }

        $to = config('eiaaw.sales_email', 'sales@eiaawsolutions.com');
        $subject = sprintf(
            '[ep.eiaawsolutions] New enquiry from %s — source: %s',
            $row->name,
            $row->source ?: 'landing-form'
        );
        $body = sprintf(
            "New marketing enquiry on ep.eiaawsolutions.com\n\n" .
            "Contact ID: %d\n" .
            "Name: %s\nEmail: %s\nPhone: %s\nCompany: %s\nSource: %s\nIP: %s\n" .
            "Submitted: %s\nAttempt: %d of %d\n\n--- Message ---\n%s\n",
            $row->id,
            $row->name,
            $row->email,
            $row->phone ?: '-',
            $row->company ?: '-',
            $row->source ?: 'landing-form',
            $row->ip ?: '-',
            $row->created_at,
            $attempts + 1,
            self::MAX_ATTEMPTS,
            $row->message
        );

        try {
            Mail::raw($body, function ($m) use ($to, $subject, $row) {
                $m->to($to)
                  ->subject($subject)
                  ->replyTo($row->email, $row->name);
            });

            DB::table('marketing_contacts')->where('id', $contactId)->update([
                'emailed_at' => now(),
                'email_attempts' => $attempts + 1,
                'email_failed_at' => null,
                'email_last_error' => null,
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            $msg = Str::limit($e->getMessage(), 240, '');

            DB::table('marketing_contacts')->where('id', $contactId)->update([
                'email_attempts' => $attempts + 1,
                'email_failed_at' => now(),
                'email_last_error' => $msg,
                'updated_at' => now(),
            ]);

            // ERROR (not WARNING) — this is a revenue-critical failure that
            // the operator must see. Log channel pipes to Sentry/file.
            Log::error('marketing_lead_notifier.send_failed', [
                'id' => $contactId,
                'attempt' => $attempts + 1,
                'max_attempts' => self::MAX_ATTEMPTS,
                'error' => $msg,
                'will_retry' => ($attempts + 1) < self::MAX_ATTEMPTS,
            ]);

            return false;
        }
    }
}
