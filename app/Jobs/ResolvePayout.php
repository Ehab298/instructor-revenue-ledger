<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\Payments\PaymentProvider;
use App\Services\Payments\ProviderStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Resolves the outcome of a timed-out payout by asking the provider what
 * actually happened. Never moves money. Claiming (timeout -> processing)
 * makes resolution idempotent: duplicate or overlapping resolvers do
 * nothing; only the winner finalizes the payout and writes the ledger.
 */
class ResolvePayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $payoutId,
    ) {}

    public function handle(PaymentProvider $provider): void
    {
        $payout = Payout::query()->find($this->payoutId);

        if (! $payout || ! $payout->claimForResolution()) {
            return;
        }

        $result = $provider->checkStatus($payout->provider_reference);

        if ($result->status === ProviderStatus::Paid) {
            $payout->markSucceeded();
        } else {
            $payout->markFailed();
        }
    }
}
