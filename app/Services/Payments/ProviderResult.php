<?php

namespace App\Services\Payments;

final readonly class ProviderResult
{
    public function __construct(
        public ProviderStatus $status,
        public string $reference,
    ) {}

    public function paid(): bool
    {
        return $this->status === ProviderStatus::Paid;
    }
}
