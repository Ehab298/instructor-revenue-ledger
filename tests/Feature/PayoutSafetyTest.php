<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Jobs\ProcessPayout;
use App\Models\Course;
use App\Models\LedgerTransaction;
use App\Models\Payout;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payments\MockOutcome;
use App\Services\Payments\MockPaymentProvider;
use App\Services\Payments\PaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class PayoutSafetyTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private MockPaymentProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->instructor()->create();
        Course::factory()->for($this->instructor, 'instructor')->create();

        LedgerTransaction::create([
            'instructor_id' => $this->instructor->id,
            'amount' => 84_000,
            'type' => 'earning',
            'status' => 'New',
        ]);

        $this->provider = new MockPaymentProvider;
        $this->app->instance(PaymentProvider::class, $this->provider);
    }

    private function useProvider(MockOutcome ...$outcomes): void
    {
        $this->provider = new MockPaymentProvider(array_values($outcomes));
        $this->app->instance(PaymentProvider::class, $this->provider);
    }

    public function test_successful_run_pays_and_deducts_balance(): void
    {
        $this->useProvider(MockOutcome::Success);

        $this->artisan('payouts:run');

        $payout = Payout::query()->sole();

        $this->assertSame('success', $payout->status);
        $this->assertSame(84_000, $payout->amount);
        $this->assertNotNull($payout->provider_reference);

        $ledgerPayout = LedgerTransaction::query()->where('type', 'payout')->sole();
        $this->assertSame(84_000, $ledgerPayout->amount);
        $this->assertSame($payout->id, $ledgerPayout->reference_id);

        $this->assertSame(1, $this->provider->payCallCount);
        $this->assertSame(0, $this->instructor->fresh()->balance);
    }

    public function test_running_the_process_twice_never_double_pays(): void
    {
        $this->useProvider(MockOutcome::Success);

        $this->artisan('payouts:run');
        $this->artisan('payouts:run');

        $this->assertSame(1, $this->provider->payCallCount);
        $this->assertSame(1, Payout::query()->where('status', 'success')->count());
        $this->assertSame(1, LedgerTransaction::query()->where('type', 'payout')->count());
        $this->assertSame(0, $this->instructor->fresh()->balance);
    }

    public function test_retried_job_after_crash_never_pays_twice(): void
    {
        $payout = Payout::create([
            'instructor_id' => $this->instructor->id,
            'amount' => 84_000,
            'status' => 'processing',
            'provider_reference' => 'ref-crashed',
        ]);

        (new ProcessPayout($payout->id))->handle($this->provider);

        $this->assertSame(0, $this->provider->payCallCount);
        $this->assertSame(0, LedgerTransaction::query()->where('type', 'payout')->count());
    }

    public function test_timeout_then_delayed_confirmation_no_duplicate(): void
    {
        $this->useProvider(MockOutcome::TimeoutAfterSuccess);

        $this->artisan('payouts:run');

        $this->assertSame(1, $this->provider->payCallCount);

        $payout = Payout::query()->sole();
        $this->assertSame('success', $payout->status);

        $this->assertSame(1, LedgerTransaction::query()->where('type', 'payout')->count());
        $this->assertSame(0, $this->instructor->fresh()->balance);
    }

    public function test_permanent_failure_keeps_balance_and_retry_pays_once(): void
    {
        $this->useProvider(MockOutcome::PermanentFailure, MockOutcome::Success);

        $this->artisan('payouts:run');

        $this->assertSame('failed', Payout::query()->sole()->status);
        $this->assertSame(84_000, $this->instructor->fresh()->balance);
        $this->assertSame(0, LedgerTransaction::query()->where('type', 'payout')->count());

        $this->artisan('payouts:run');

        $this->assertSame(2, $this->provider->payCallCount);
        $this->assertSame(1, LedgerTransaction::query()->where('type', 'payout')->count());
        $this->assertSame(0, $this->instructor->fresh()->balance);
    }

    public function test_subscription_records_instructor_earnings(): void
    {
        $student = User::factory()->create();

        $this->post('/subscriptions', [
            'student_id' => $student->id,
            'course_ids' => [$this->instructor->courses()->first()->id],
            'plan' => 'monthly',
        ])->assertSessionHasNoErrors();

        $subscription = Subscription::query()->sole();
        $earning = $subscription->ledgerTransactions()->where('type', 'earning')->sole();

        $this->assertSame(84_000, $earning->amount); // 70% of 1,200 EGP
        $this->assertSame($this->instructor->id, $earning->instructor_id);
        $this->assertSame('New', $earning->status);
        $this->assertSame($subscription->id, $earning->reference_id);
        $this->assertSame(Subscription::class, $earning->reference_type);
        $this->assertSame(SubscriptionPlan::Monthly, $subscription->plan);
        $this->assertSame(120_000, $subscription->amount_paid);
    }
}
