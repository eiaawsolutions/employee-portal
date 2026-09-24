<?php

namespace App\Services\Billing;

/**
 * The handful of Stripe calls the signup checkout needs. Kept behind an
 * interface so SignupCheckout can be tested without the network.
 *
 * Checkout sessions are returned normalised to plain arrays:
 *   ['id', 'status', 'payment_status', 'customer', 'subscription', 'metadata' => [...]]
 * with customer/subscription as IDs — the same shape a checkout.session.completed
 * webhook payload carries.
 */
interface StripeGateway
{
    public function findPriceIdByLookupKey(string $lookupKey): ?string;

    /** Create a recurring Price (with inline product_data); returns its ID. */
    public function createPrice(array $params): string;

    /** @return array{id: string, url: string} */
    public function createCheckoutSession(array $params): array;

    public function retrieveCheckoutSession(string $id): array;
}
