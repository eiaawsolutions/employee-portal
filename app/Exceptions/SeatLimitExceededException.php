<?php

namespace App\Exceptions;

use App\Models\Tenant;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown when a tenant tries to add a user beyond their plan_seats limit.
 *
 * Returns HTTP 402 Payment Required — the canonical "this requires an
 * upgrade" response. Browser middleware renders a friendly page; SSO/JIT
 * provisioning paths catch it and surface the message to the IdP.
 */
class SeatLimitExceededException extends HttpException
{
    public function __construct(
        public readonly Tenant $tenant,
        public readonly int $seatsUsed,
        public readonly int $seatLimit,
    ) {
        parent::__construct(
            statusCode: 402,
            message: sprintf(
                'Plan limit reached: %d of %d seats used on the %s plan. Upgrade to add more users.',
                $seatsUsed,
                $seatLimit,
                $tenant->plan,
            ),
        );
    }
}
