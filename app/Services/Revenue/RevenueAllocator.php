<?php

namespace App\Services\Revenue;

use InvalidArgumentException;

/**
 * Splits a subscription payment: exactly 30% for the platform, exactly 70%
 * for the instructors, divided equally (integer piastres only).
 *
 * When the 70% pool does not divide evenly, the leftover piastres are handed
 * out one by one to the first instructors, so shares differ by at most one
 * piastre and NO money is ever lost: platform + instructors = total.
 */
class RevenueAllocator
{
    public const PLATFORM_FEE_PERCENT = 30;

    /**
     * @param  int  $amountInPiastres  Total payment in piastres
     * @param  array<int>  $instructorIds  Instructors sharing the pool
     */
    public function split(int $amountInPiastres, array $instructorIds): RevenueSplit
    {
        if ($amountInPiastres <= 0) {
            throw new InvalidArgumentException('Amount must be a positive integer of piastres.');
        }

        $instructorIds = array_values(array_unique($instructorIds));

        if ($instructorIds === []) {
            throw new InvalidArgumentException('At least one instructor is required to split revenue.');
        }

        $poolPercent = 100 - self::PLATFORM_FEE_PERCENT;

        $instructorPool = intdiv($amountInPiastres * $poolPercent, 100);
        $platformFee = $amountInPiastres - $instructorPool;

        $count = count($instructorIds);
        $baseShare = intdiv($instructorPool, $count);
        $leftover = $instructorPool - ($baseShare * $count);

        $instructorAmounts = [];
        foreach ($instructorIds as $index => $instructorId) {
            $instructorAmounts[$instructorId] = $baseShare + ($index < $leftover ? 1 : 0);
        }

        return new RevenueSplit($platformFee, $instructorAmounts);
    }
}
