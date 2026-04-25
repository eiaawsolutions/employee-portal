<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown when a tenant slug fails availability check at provisioning
 * time — race-condition guard for the narrow window between signup form
 * validation and DB INSERT, where another concurrent signup might have
 * claimed the same slug. The DB UNIQUE constraint is the ultimate
 * defense; this exception exists to surface a friendly HTTP 409 instead
 * of a 500 constraint-violation page.
 */
class SlugUnavailableException extends HttpException
{
    public function __construct(public readonly string $slug)
    {
        parent::__construct(
            statusCode: 409,
            message: sprintf(
                'The workspace URL "%s" was claimed by another signup just now. Please choose a different one.',
                $slug,
            ),
        );
    }
}
