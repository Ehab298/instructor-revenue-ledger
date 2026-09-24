<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\Payments\PaymentProvider;
use App\Services\Payments\ProviderStatus;
use App\Services\Payments\ProviderTimeoutException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly int $payoutId,
    ) {}

    public function handle(PaymentProvider $provider): void
    {
        $payout = Payout::query()->find($this->payoutId);

        if (! $payout || ! $payout->claim()) {
            return; // Already owned by another (retried/concurrent) execution.
        }

        try {
            $result = $provider->pay($payout->amount, $payout->provider_reference);
        } catch (ProviderTimeoutException) {
            $payout->markTimedOut();

            ResolvePayout::dispatch($this->payoutId);

            return;
        }

        if ($result->status === ProviderStatus::Paid) {
            $payout->markSucceeded();
        } else {
            $payout->markFailed();
        }
    }
}
