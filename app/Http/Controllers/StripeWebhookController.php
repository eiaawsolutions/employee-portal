<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionEvent;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stripe webhook handler — extends Cashier's default with:
 *
 *   1. Idempotency: every Stripe event ID is recorded in subscription_events
 *      before processing, with a unique constraint preventing duplicate runs
 *      (Stripe retries failed deliveries up to 3 days).
 *
 *   2. Tenant resolution: webhooks arrive without our tenant subdomain,
 *      so we resolve the tenant from the customer ID in the payload and run
 *      the rest of the logic inside TenantContext::run().
 *
 *   3. Failed payment → suspend tenant after a 3-day grace period.
 *      Successful renewal payment → un-suspend.
 *
 *   4. Subscription canceled → mark tenant as canceled (read-only access
 *      for 30 days, then hard delete via a separate scheduled job).
 *
 * The route is registered in routes/web.php as POST /stripe/webhook with the
 * VerifyWebhookSignature middleware (requires STRIPE_WEBHOOK_SECRET in .env).
 */
class StripeWebhookController extends CashierWebhookController
{
    /**
     * Override the framework's main entrypoint to add idempotency + tenant
     * context BEFORE delegating to Cashier's per-event handlers.
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        $eventId = $payload['id'] ?? null;
        $eventType = $payload['type'] ?? null;

        if (!$eventId || !$eventType) {
            return new Response('Bad payload', 400);
        }

        // Idempotency: record the event. If we've seen this event_id before,
        // the unique constraint on stripe_event_id rejects the insert and we
        // skip processing.
        $existing = SubscriptionEvent::where('stripe_event_id', $eventId)->first();
        if ($existing && $existing->processed_at) {
            return new Response('Already processed', 200);
        }

        $tenantId = $this->resolveTenantFromPayload($payload);

        $event = $existing ?: SubscriptionEvent::create([
            'tenant_id'       => $tenantId,
            'stripe_event_id' => $eventId,
            'event_type'      => $eventType,
            'payload'         => $payload,
        ]);

        try {
            // Run Cashier's handler INSIDE the resolved tenant's context so
            // any subscription queries / writes hit the right RLS scope.
            if ($tenantId) {
                $tenant = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                    ->find($tenantId);
                if ($tenant) {
                    TenantContext::run($tenant, fn () => parent::handleWebhook($request));
                    $this->applySaaSSideEffects($eventType, $payload, $tenant);
                } else {
                    parent::handleWebhook($request);
                }
            } else {
                parent::handleWebhook($request);
            }

            $event->update(['processed_at' => now()]);
            return new Response('OK', 200);
        } catch (\Throwable $e) {
            Log::error("Stripe webhook {$eventType} ({$eventId}) failed: " . $e->getMessage(), [
                'tenant_id' => $tenantId,
                'trace'     => $e->getTraceAsString(),
            ]);
            $event->update(['processing_error' => substr($e->getMessage(), 0, 1000)]);
            // Return 500 so Stripe retries; idempotency above prevents
            // double-processing once we recover.
            return new Response('Processing error', 500);
        }
    }

    /**
     * Stripe payloads embed the customer ID in different shapes per event type.
     * Try the common locations.
     */
    private function resolveTenantFromPayload(array $payload): ?int
    {
        $customerId = $payload['data']['object']['customer']
            ?? $payload['data']['object']['id']  // for customer.* events the object IS the customer
            ?? null;

        if (!$customerId) {
            return null;
        }

        return Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('stripe_id', $customerId)
            ->orWhere('stripe_customer_id', $customerId)
            ->value('id');
    }

    /**
     * EIAAW-specific side effects on top of Cashier's standard subscription
     * handling: tenant suspension, un-suspension, and cancellation.
     */
    private function applySaaSSideEffects(string $eventType, array $payload, Tenant $tenant): void
    {
        switch ($eventType) {
            case 'invoice.payment_failed':
                // Mark tenant past_due + record WHEN. The PastDueSuspension
                // scheduled command (Wk3) suspends the tenant after a 3-day
                // grace period measured from past_due_at.
                $tenant->update([
                    'subscription_status' => 'past_due',
                    'past_due_at' => $tenant->past_due_at ?? now(),
                ]);
                Log::warning("Tenant {$tenant->slug} payment failed — past_due flag set.");
                break;

            case 'invoice.payment_succeeded':
                // Clear past_due state on any successful payment.
                $updates = [
                    'subscription_status' => 'active',
                    'past_due_at' => null,
                ];
                if ($tenant->status === Tenant::STATUS_SUSPENDED) {
                    $updates['status'] = Tenant::STATUS_ACTIVE;
                    $updates['suspended_at'] = null;
                    $updates['suspension_reason'] = null;
                    Log::info("Tenant {$tenant->slug} un-suspended after payment.");
                }
                $tenant->update($updates);
                break;

            case 'customer.subscription.deleted':
                $tenant->update([
                    'status' => Tenant::STATUS_CANCELED,
                    'subscription_status' => 'canceled',
                    'canceled_at' => $tenant->canceled_at ?? now(),
                ]);
                Log::info("Tenant {$tenant->slug} subscription canceled — 30-day read-only grace begins.");
                break;
        }
    }
}
