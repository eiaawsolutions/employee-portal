<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Enforces the retention periods in the privacy notice (/privacy §7):
 *   - enquiries (marketing_contacts) and chat logs (marketing_chat_log):
 *     deleted 24 months after they were last touched
 *   - signups whose email was never confirmed: deleted after 90 days
 */
class PurgeStaleMarketingData extends Command
{
    protected $signature = 'marketing:purge-stale {--dry-run : Count what would be deleted without deleting}';

    protected $description = 'Delete enquiry, chat-log and unconfirmed-signup data past its retention period';

    public function handle(): int
    {
        $enquiryCutoff = now()->subMonths(24);
        $signupCutoff = now()->subDays(90);

        $targets = [
            'marketing_contacts' => DB::table('marketing_contacts')->where('updated_at', '<', $enquiryCutoff),
            'marketing_chat_log' => DB::table('marketing_chat_log')->where('created_at', '<', $enquiryCutoff),
            'signup_invites (unconfirmed)' => DB::table('signup_invites')->whereNull('confirmed_at')->where('created_at', '<', $signupCutoff),
        ];

        foreach ($targets as $label => $query) {
            $count = $this->option('dry-run') ? $query->count() : $query->delete();
            $this->info(($this->option('dry-run') ? '[dry-run] ' : '')."{$label}: {$count}");
        }

        return self::SUCCESS;
    }
}
