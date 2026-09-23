<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionPlan;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscriptions\CreateSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{

    public function create(Request $request): View
    {
        $students = User::query()
            ->where('role', 'student')
            ->orderBy('name')
            ->get();

        $courses = Course::query()
            ->with('instructor')
            ->orderBy('title')
            ->get();


        $selectedStudentId = (int) (old('student_id', $request->query('student_id')) ?? 0);

        $activeSubscriptions = Subscription::query()
            ->active()
            ->with(['courses', 'student'])
            ->where('student_id', $selectedStudentId)
            ->orderByDesc('ends_at')
            ->get();

        return view('subscriptions.create', [
            'students' => $students,
            'courses' => $courses,
            'plans' => SubscriptionPlan::cases(),
            'activeSubscriptions' => $activeSubscriptions,
            'selectedStudentId' => $selectedStudentId,
        ]);
    }

    public function store(StoreSubscriptionRequest $request, CreateSubscription $createSubscription): RedirectResponse
    {
        $subscription = $createSubscription(
            User::query()->findOrFail($request->validated('student_id')),
            Course::query()->whereIn('id', $request->validated('course_ids'))->get(),
            SubscriptionPlan::from($request->validated('plan')),
        );

        return redirect()
            ->route('subscriptions.create', ['student_id' => $subscription->student_id])
            ->with('status', sprintf(
                '%s is subscribed to %s (%s) for %s. Access ends %s.',
                $subscription->student->name,
                $subscription->courses->pluck('title')->implode(', '),
                $subscription->plan->label(),
                $subscription->plan->formattedPrice(),
                $subscription->ends_at->format('d M Y'),
            ));
    }
}
