<?php

namespace App\Services\Subscriptions;

use App\Enums\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Revenue\AllocateSubscriptionEarnings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSubscription
{
    public function __construct(
        private readonly AllocateSubscriptionEarnings $allocateEarnings,
    ) {}

    public function __invoke(User $student, Collection $courses, SubscriptionPlan $plan): Subscription
    {
        $courses = $courses->unique('id')->values();

        return DB::transaction(function () use ($student, $courses, $plan): Subscription {

            User::query()->whereKey($student->id)->lockForUpdate()->first();

            $this->assertNoActiveOverlap($student, $courses);

            $startsAt = now();

            $subscription = Subscription::create([
                'student_id' => $student->id,
                'plan' => $plan,
                'amount_paid' => $plan->priceInPiastres(),
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMonthsNoOverflow($plan->durationInMonths()),
            ]);

            $subscription->courses()->sync($courses->pluck('id')->all());

            ($this->allocateEarnings)($subscription);

            return $subscription;
        });
    }

    private function assertNoActiveOverlap(User $student, Collection $courses): void
    {
        $courseIds = $courses->pluck('id')->all();

        $overlappingCourseTitles = Subscription::query()
            ->where('student_id', $student->id)
            ->active()
            ->whereHas('courses', fn ($query) => $query->whereIn('courses.id', $courseIds))
            ->with('courses:id,title')
            ->get()
            ->flatMap->courses
            ->pluck('title')
            ->unique()
            ->values()
            ->all();

        if ($overlappingCourseTitles !== []) {
            throw ValidationException::withMessages([
                'course_ids' => 'You already have an active subscription that includes: '.implode(', ', $overlappingCourseTitles).'.',
            ]);
        }
    }
}
