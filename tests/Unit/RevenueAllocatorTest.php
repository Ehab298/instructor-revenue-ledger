<?php

namespace Tests\Unit;

use App\Services\Payments\MockOutcome;
use App\Services\Payments\MockPaymentProvider;
use App\Services\Payments\ProviderStatus;
use App\Services\Payments\ProviderTimeoutException;
use App\Services\Revenue\RevenueAllocator;
use PHPUnit\Framework\TestCase;

class RevenueAllocatorTest extends TestCase
{
    private RevenueAllocator $allocator;

    protected function setUp(): void
    {
        $this->allocator = new RevenueAllocator;
    }

    public function test_single_instructor_gets_whole_pool(): void
    {
        $split = $this->allocator->split(120_000, [7]); 

        $this->assertSame(36_000, $split->platformFee); 
        $this->assertSame([7 => 84_000], $split->instructorAmounts); 
        $this->assertSame(120_000, $split->totalAllocated()); 
    }

    public function test_pool_splits_equally_across_instructors(): void
    {
        $split = $this->allocator->split(120_000, [1, 2, 3]);

        $this->assertSame(36_000, $split->platformFee);
        $this->assertSame([1 => 28_000, 2 => 28_000, 3 => 28_000], $split->instructorAmounts);
        $this->assertSame(120_000, $split->totalAllocated());
    }

    public function test_uneven_split_keeps_leftover_with_instructors(): void
    {
        $split = $this->allocator->split(100_000, [1, 2, 3]);

        $this->assertSame(30_000, $split->platformFee);
        $this->assertSame([1 => 23_334, 2 => 23_333, 3 => 23_333], $split->instructorAmounts);
        $this->assertSame(100_000, $split->totalAllocated());
    }

    public function test_duplicate_instructor_counted_once(): void
    {
        $split = $this->allocator->split(120_000, [7, 7]);

        $this->assertSame([7 => 84_000], $split->instructorAmounts);
    }

    public function test_invalid_input_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->allocator->split(0, [1]);

        $this->expectException(\InvalidArgumentException::class);
        $this->allocator->split(120_000, []);
    }

    public function test_timeout_hides_truth_discoverable_via_check_status(): void
    {
        $provider = new MockPaymentProvider([MockOutcome::TimeoutAfterSuccess]);

        try {
            $provider->pay(84_000, 'ref-1');
            $this->fail('A timeout should have been thrown.');
        } catch (ProviderTimeoutException $exception) {
            $this->assertSame('ref-1', $exception->reference);
        }

        $this->assertSame(ProviderStatus::Paid, $provider->checkStatus('ref-1')->status);
        $this->assertSame(1, $provider->payCallCount);
    }
}
