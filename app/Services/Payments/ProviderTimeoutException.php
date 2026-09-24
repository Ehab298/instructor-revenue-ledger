<?php

namespace App\Services\Payments;

use RuntimeException;

final class ProviderTimeoutException extends RuntimeException
{
    public function __construct(public readonly string $reference)
    {
        parent::__construct("Payment provider timed out for reference [{$reference}]; outcome unknown.");
    }
}
