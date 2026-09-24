<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class MockPaymentProvider implements PaymentProvider
{
    /** @var array<string, ProviderStatus> The truth, keyed by reference. */
    private array $truth = [];

    /** @var list<MockOutcome> */
    private readonly array $outcomes;

    private int $cursor = 0;

    public int $payCallCount = 0;

    public function __construct(array $outcomes = [])
    {
        foreach ($outcomes as $outcome) {
            if (! $outcome instanceof MockOutcome) {
                throw new InvalidArgumentException('Outcomes must be MockOutcome cases.');
            }
        }

        $this->outcomes = array_values($outcomes);
    }

    public function pay(int $amountInPiastres, string $reference): ProviderResult
    {
        if ($amountInPiastres <= 0) {
            throw new InvalidArgumentException('Amount must be a positive integer of piastres.');
        }

        $this->payCallCount++;

        $outcome = $this->nextOutcome();

        $this->truth[$reference] = match ($outcome) {
            MockOutcome::Success, MockOutcome::TimeoutAfterSuccess => ProviderStatus::Paid,
            MockOutcome::PermanentFailure, MockOutcome::TimeoutAfterFailure => ProviderStatus::Failed,
        };

        if ($outcome === MockOutcome::TimeoutAfterSuccess || $outcome === MockOutcome::TimeoutAfterFailure) {
            throw new ProviderTimeoutException($reference);
        }

        return new ProviderResult($this->truth[$reference], $reference);
    }

    public function checkStatus(string $reference): ProviderResult
    {
        return new ProviderResult($this->truth[$reference] ?? ProviderStatus::Failed, $reference);
    }

    private function nextOutcome(): MockOutcome
    {
        if ($this->outcomes !== []) {
            return $this->outcomes[min($this->cursor++, count($this->outcomes) - 1)];
        }

        return MockOutcome::cases()[array_rand(MockOutcome::cases())];
    }
}
