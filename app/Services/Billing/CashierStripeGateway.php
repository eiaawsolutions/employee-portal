<?php

namespace App\Services\Billing;

use Laravel\Cashier\Cashier;

/**
 * Production StripeGateway — Cashier's configured Stripe client, so the
 * secret comes from the same place as the rest of billing (cashier.secret,
 * resolved via Infisical / platform settings).
 */
class CashierStripeGateway implements StripeGateway
{
    public function findPriceIdByLookupKey(string $lookupKey): ?string
    {
        $prices = Cashier::stripe()->prices->all([
            'lookup_keys' => [$lookupKey],
            'active'      => true,
            'limit'       => 1,
        ]);

        return $prices->data[0]->id ?? null;
    }

    public function createPrice(array $params): string
    {
        return Cashier::stripe()->prices->create($params)->id;
    }

    public function createCheckoutSession(array $params): array
    {
        $session = Cashier::stripe()->checkout->sessions->create($params);

        return ['id' => $session->id, 'url' => (string) $session->url];
    }

    public function retrieveCheckoutSession(string $id): array
    {
        $s = Cashier::stripe()->checkout->sessions->retrieve($id);

        return [
            'id'             => $s->id,
            'status'         => $s->status,
            'payment_status' => $s->payment_status,
            'customer'       => is_string($s->customer) ? $s->customer : ($s->customer->id ?? null),
            'subscription'   => is_string($s->subscription) ? $s->subscription : ($s->subscription->id ?? null),
            'metadata'       => $s->metadata ? $s->metadata->toArray() : [],
        ];
    }
}
