<?php

namespace App\Services\Payments;

interface PaymentProvider
{
    public function pay(int $amountInPiastres, string $reference): ProviderResult;

    public function checkStatus(string $reference): ProviderResult;
}
