<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * StripeSyncPrices — create/verify the 12 recurring Prices that EIAAW
 * Workforce bills against.
 *
 * Behaviour:
 *   --dry-run   (default when STRIPE_SECRET starts with sk_live_): report
 *               what would be created/updated; change nothing
 *   --apply     actually create the Products + Prices
 *   --emit-env  print the .env block with the resulting price IDs
 *
 * Product shape:
 *   One Stripe Product per tier ("EIAAW Workforce — Starter" etc.)
 *   Four Prices per Product (MYR/USD × monthly/annual)
 *
 * Price lookup_key — we use a deterministic key per slot so this command
 * is idempotent AND a human in the Stripe dashboard can audit the match:
 *   eiaaw_workforce_{tier}_{ccy_lower}_{period}
 *   e.g. eiaaw_workforce_starter_myr_monthly
 *
 * Enterprise is skipped — manual invoicing, no Price object.
 *
 * Production safety: against a sk_live_ key this refuses to mutate unless
 * --apply AND --i-know-this-is-live are both passed.
 */
class StripeSyncPrices extends Command
{
    protected $signature = 'stripe:sync-prices
        {--dry-run : Report what would be created, no writes (default for live keys)}
        {--apply : Actually create/update Stripe objects}
        {--i-know-this-is-live : Required alongside --apply when STRIPE_SECRET is a live key}
        {--emit-env : After sync, print the .env block to paste into production}';

    protected $description = 'Create (or verify) the 12 Stripe Prices that back EIAAW Workforce subscriptions.';

    private const PRODUCT_NAME_PREFIX = 'EIAAW Workforce — ';
    private const METADATA_OWNER = 'eiaaw-workforce-sync';

    public function handle(): int
    {
        $secret = env('STRIPE_SECRET');
        if (empty($secret)) {
            $this->error('STRIPE_SECRET is not set.');
            return self::FAILURE;
        }

        $isLive = str_starts_with($secret, 'sk_live_');
        $apply = (bool) $this->option('apply');
        $dryRun = (bool) $this->option('dry-run') || !$apply;

        if ($isLive && $apply && !$this->option('i-know-this-is-live')) {
            $this->error('Refusing to write to a LIVE Stripe account without --i-know-this-is-live.');
            return self::FAILURE;
        }

        $mode = $isLive ? 'LIVE' : 'test';
        $action = $dryRun ? '[dry-run] ' : '';
        $this->info("{$action}Syncing Stripe Prices against {$mode} account.");

        $tiers = config('eiaaw.pricing.tiers', []);
        $annualFactor = 12 - (int) config('eiaaw.pricing.annual_months_free', 2); // 10

        $envLines = [];

        foreach (['starter', 'growth', 'scale'] as $tierKey) {
            $tier = $tiers[$tierKey] ?? null;
            if (!$tier) {
                $this->warn("  skipped: tier '{$tierKey}' missing from config");
                continue;
            }

            $this->line("→ Tier {$tier['name']}");

            $product = $this->ensureProduct($secret, $tierKey, $tier, $dryRun);
            if (!$product) {
                $this->error("  failed to ensure product for {$tierKey}");
                return self::FAILURE;
            }

            // Session 11: USD-only. Six Prices total = 3 tiers × monthly/annual.
            $monthly = $tier['monthly_usd'] ?? null;
            if ($monthly === null) continue;

            foreach (['monthly', 'annual'] as $period) {
                $unit = $period === 'monthly' ? $monthly : ($monthly * $annualFactor);
                $interval = $period === 'monthly' ? 'month' : 'year';
                $lookupKey = "eiaaw_workforce_{$tierKey}_usd_{$period}";

                $price = $this->ensurePrice(
                    secret: $secret,
                    productId: $product['id'],
                    lookupKey: $lookupKey,
                    unitAmount: $unit * 100,  // Stripe uses smallest currency unit (cents)
                    currency: 'usd',
                    interval: $interval,
                    dryRun: $dryRun,
                );

                $envKey = strtoupper("STRIPE_PRICE_{$tierKey}_USD_{$period}");
                $priceId = $price['id'] ?? 'price_(would-create)';
                $amount = number_format($unit, 0);
                $this->line("    {$envKey}={$priceId}  (USD {$amount} / {$interval})");
                $envLines[$envKey] = $priceId;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->comment('Dry run complete. Re-run with --apply to actually create the objects.');
        } else {
            $this->info('Sync complete. Save these IDs to your production environment:');
        }

        if ($this->option('emit-env') || !$dryRun) {
            $this->newLine();
            $this->line('# ── Stripe Price IDs (paste into production env) ──');
            foreach ($envLines as $k => $v) {
                $this->line("{$k}={$v}");
            }
        }

        return self::SUCCESS;
    }

    private function ensureProduct(string $secret, string $tierKey, array $tier, bool $dryRun): ?array
    {
        $productKey = "eiaaw_workforce_{$tierKey}";
        $name = self::PRODUCT_NAME_PREFIX . $tier['name'];

        // Stripe doesn't support lookup_key on Products — we search by name.
        $search = $this->request($secret, 'GET', 'products/search', [
            'query' => "name:'{$name}' AND active:'true'",
            'limit' => 1,
        ]);

        if ($search->successful() && !empty($search['data'])) {
            $existing = $search['data'][0];
            $this->line("  product exists: {$existing['id']}");
            return $existing;
        }

        if ($dryRun) {
            $this->line("  product would be CREATED: {$name}");
            return ['id' => 'prod_(would-create)'];
        }

        $created = $this->request($secret, 'POST', 'products', [
            'name' => $name,
            'description' => $tier['tagline'] ?? '',
            "metadata[owner]" => self::METADATA_OWNER,
            "metadata[tier]" => $tierKey,
        ]);

        if (!$created->successful()) {
            $this->error('  product create failed: ' . $created->body());
            return null;
        }

        $this->line("  product created: {$created['id']}");
        return $created->json();
    }

    private function ensurePrice(
        string $secret,
        string $productId,
        string $lookupKey,
        int $unitAmount,
        string $currency,
        string $interval,
        bool $dryRun,
    ): array {
        $existing = $this->request($secret, 'GET', 'prices', [
            'lookup_keys[]' => $lookupKey,
            'active' => 'true',
            'limit' => 1,
        ]);

        if ($existing->successful() && !empty($existing['data'])) {
            $p = $existing['data'][0];
            // Validate that the existing Price matches what we expect; warn if it doesn't.
            if ($p['unit_amount'] !== $unitAmount || $p['currency'] !== $currency) {
                $this->warn("    ! price {$lookupKey} EXISTS but amount/currency differs ({$p['unit_amount']}/{$p['currency']} vs {$unitAmount}/{$currency}) — Stripe does not allow editing; archive + recreate manually");
            }
            return $p;
        }

        if ($dryRun) {
            return ['id' => 'price_(would-create)', 'lookup_key' => $lookupKey];
        }

        $created = $this->request($secret, 'POST', 'prices', [
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => $unitAmount,
            'lookup_key' => $lookupKey,
            'recurring[interval]' => $interval,
            'billing_scheme' => 'per_unit',
            "metadata[owner]" => self::METADATA_OWNER,
        ]);

        if (!$created->successful()) {
            $this->error("    price create failed ({$lookupKey}): " . $created->body());
            return ['id' => null];
        }

        return $created->json();
    }

    private function request(string $secret, string $method, string $path, array $params = []): Response
    {
        $url = 'https://api.stripe.com/v1/' . ltrim($path, '/');
        $client = Http::withBasicAuth($secret, '')
            ->timeout(15)
            ->acceptJson();

        if ($method === 'GET') {
            return $client->get($url, $params);
        }
        return $client->asForm()->post($url, $params);
    }
}
