<?php

namespace App\Services\Revenue;

use App\Models\Subscription;

/**
 * Records one "earning" ledger row per instructor for a paid subscription,
 * morph-referenced to the subscription. Must run inside the subscription
 * creation transaction so both are committed together (or not at all).
 *
 * Refunds/cancellations are intentionally out of scope: earnings, once
 * recorded, stay recorded.
 */
class AllocateSubscriptionEarnings
{
    public function __construct(
        private readonly RevenueAllocator $allocator,
    ) {}

    public function __invoke(Subscription $subscription): RevenueSplit
    {
        $subscription->loadMissing('courses');

        $instructorIds = $subscription->courses
            ->pluck('instructor_id')
            ->unique()
            ->values()
            ->all();

        $split = $this->allocator->split($subscription->amount_paid, $instructorIds);

        foreach ($split->instructorAmounts as $instructorId => $amount) {
            $subscription->ledgerTransactions()->create([
                'instructor_id' => $instructorId,
                'amount' => $amount,
                'type' => 'earning',
                'status' => 'New',
            ]);
        }

        return $split;
    }
}
