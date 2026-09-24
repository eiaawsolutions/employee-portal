<?php

namespace Tests\Fakes;

use App\Services\Billing\StripeGateway;

/**
 * In-memory Stripe for signup-checkout tests. Records every call so tests
 * can assert on exactly what would have been sent to Stripe.
 */
class FakeStripeGateway implements StripeGateway
{
    /** @var array<string, string> lookup_key => price id */
    public array $pricesByLookupKey = [];

    /** @var array<int, array> */
    public array $createdPrices = [];

    /** @var array<int, array> */
    public array $createdSessions = [];

    /** @var array<string, array> session id => normalised session */
    public array $sessions = [];

    public function findPriceIdByLookupKey(string $lookupKey): ?string
    {
        return $this->pricesByLookupKey[$lookupKey] ?? null;
    }

    public function createPrice(array $params): string
    {
        $id = 'price_fake_' . (count($this->createdPrices) + 1);
        $this->createdPrices[] = $params;
        $this->pricesByLookupKey[$params['lookup_key']] = $id;

        return $id;
    }

    public function createCheckoutSession(array $params): array
    {
        $id = 'cs_test_fake_' . (count($this->createdSessions) + 1);
        $this->createdSessions[] = $params;
        $this->sessions[$id] = [
            'id'             => $id,
            'status'         => 'open',
            'payment_status' => 'unpaid',
            'customer'       => null,
            'subscription'   => null,
            'metadata'       => $params['metadata'] ?? [],
        ];

        return ['id' => $id, 'url' => 'https://checkout.stripe.com/c/pay/' . $id];
    }

    public function retrieveCheckoutSession(string $id): array
    {
        return $this->sessions[$id] ?? throw new \RuntimeException("No such checkout session: {$id}");
    }

    /** Simulate the customer completing payment on Stripe. */
    public function completeSession(string $id): array
    {
        $this->sessions[$id] = array_merge($this->sessions[$id], [
            'status'         => 'complete',
            'payment_status' => 'paid',
            'customer'       => 'cus_fake_1',
            'subscription'   => 'sub_fake_1',
        ]);

        return $this->sessions[$id];
    }
}
