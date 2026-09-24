<?php

namespace App\Services\Revenue;

/**
 * Result of splitting a subscription payment between the platform and the
 * instructors. All amounts are integer piastres.
 */
final readonly class RevenueSplit
{
    /**
     * @param  int  $platformFee  Exactly the platform's 30% cut
     * @param  array<int, int>  $instructorAmounts  instructor_id => piastres
     */
    public function __construct(
        public int $platformFee,
        public array $instructorAmounts,
    ) {}

    public function totalAllocated(): int
    {
        return $this->platformFee + array_sum($this->instructorAmounts);
    }
}
