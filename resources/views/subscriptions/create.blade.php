<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscribe to courses</title>
    <style>
        :root {
            --bg: #f5f6f8;
            --card: #ffffff;
            --ink: #1f2933;
            --muted: #6b7280;
            --line: #e5e7eb;
            --brand: #b45309;
            --brand-ink: #ffffff;
            --brand-soft: #fef3c7;
            --ok: #065f46;
            --ok-soft: #d1fae5;
            --err: #991b1b;
            --err-soft: #fee2e2;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.5;
        }
        .wrap { max-width: 880px; margin: 0 auto; padding: 40px 20px 72px; }
        header { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 28px; }
        h1 { font-size: 1.6rem; margin: 0; }
        .banner { border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: .95rem; }
        .banner.ok { background: var(--ok-soft); color: var(--ok); }
        .banner.err { background: var(--err-soft); color: var(--err); }
        .banner ul { margin: 0; padding-left: 18px; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .card h2 { font-size: 1.05rem; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--line); }
        th { color: var(--muted); font-weight: 600; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        form { display: grid; gap: 24px; }
        label.field { display: grid; gap: 6px; font-weight: 600; font-size: .92rem; }
        select {
            font: inherit; padding: 10px 12px; border: 1px solid var(--line);
            border-radius: 10px; background: #fff; color: var(--ink); width: 100%;
        }
        .courses { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px; }
        .course {
            display: flex; gap: 10px; align-items: flex-start; border: 2px solid var(--line);
            border-radius: 12px; padding: 10px 12px; cursor: pointer; background: #fff; font-size: .92rem;
        }
        .course:has(input:checked) { border-color: var(--brand); background: var(--brand-soft); }
        .course.off { opacity: .55; cursor: not-allowed; }
        .course input { margin-top: 3px; accent-color: var(--brand); }
        .course .meta { color: var(--muted); font-size: .8rem; }
        .plans { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
        .plan {
            position: relative; display: block; border: 2px solid var(--line);
            border-radius: 12px; padding: 16px; cursor: pointer; background: #fff;
        }
        .plan:has(input:checked) { border-color: var(--brand); background: var(--brand-soft); }
        .plan input { position: absolute; opacity: 0; }
        .plan .name { font-weight: 700; }
        .plan .months { color: var(--muted); font-size: .82rem; }
        .plan .price { font-size: 1.2rem; font-weight: 700; margin-top: 6px; font-variant-numeric: tabular-nums; }
        .note { color: var(--muted); font-size: .82rem; }
        button {
            justify-self: start; border: 0; border-radius: 10px; cursor: pointer;
            background: var(--brand); color: var(--brand-ink);
            font: inherit; font-weight: 700; padding: 12px 22px;
        }
        button:hover { filter: brightness(.92); }
        .empty { color: var(--muted); font-size: .92rem; }
    </style>
</head>
<body>
<div class="wrap">
    <header>
        <h1>Subscribe to courses</h1>
    </header>

    @if (session('status'))
        <div class="banner ok" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="banner err" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card">
        <h2>Choose courses and a plan</h2>
        @if ($courses->isEmpty())
            <p class="empty">No courses are available yet.</p>
        @else
            @php
                $subscribedCourseIds = $activeSubscriptions
                    ->flatMap->courses
                    ->pluck('id')
                    ->unique()
                    ->all();
                $oldCourseIds = array_map('strval', (array) old('course_ids', []));
            @endphp
            <form method="POST" action="{{ route('subscriptions.store') }}">
                @csrf

                <label class="field">
                    Student
                    <select name="student_id" required>
                        <option value="" disabled {{ old('student_id') ? '' : 'selected' }}>Select a student…</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}"
                                    {{ (string) old('student_id', $selectedStudentId) === (string) $student->id ? 'selected' : '' }}>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <fieldset class="field" style="border:0;padding:0;margin:0">
                    Courses
                    <div class="courses">
                        @foreach ($courses as $course)
                            @php $alreadyActive = in_array($course->id, $subscribedCourseIds); @endphp
                            <label class="course {{ $alreadyActive ? 'off' : '' }}">
                                <input type="checkbox" name="course_ids[]" value="{{ $course->id }}"
                                       {{ in_array((string) $course->id, $oldCourseIds, true) ? 'checked' : '' }}
                                       {{ $alreadyActive ? 'disabled' : '' }}>
                                <span>
                                    <span>{{ $course->title }}</span>
                                    <div class="meta">
                                        {{ $course->instructor->name }}
                                        @if ($alreadyActive)
                                            — already subscribed
                                        @endif
                                    </div>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="plans" style="border:0;padding:0;margin:0">
                    <legend class="field" style="font-weight:600;font-size:.92rem;margin-bottom:8px">Plan</legend>
                    @foreach ($plans as $plan)
                        <label class="plan">
                            <input type="radio" name="plan" value="{{ $plan->value }}"
                                   required {{ old('plan', 'monthly') === $plan->value ? 'checked' : '' }}>
                            <span class="name">{{ $plan->label() }}</span>
                            <span class="months">{{ $plan->durationInMonths() }} month{{ $plan->durationInMonths() > 1 ? 's' : '' }} of access</span>
                            <div class="price">{{ $plan->formattedPrice() }}</div>
                        </label>
                    @endforeach
                </fieldset>

                <p class="note">
                    The full amount is charged upfront once per subscription. The 70% instructor pool is split
                    equally among the instructors of the selected courses; the platform keeps 30%.
                </p>

                <button type="submit">Pay &amp; subscribe</button>
            </form>
        @endif
    </section>
</div>
</body>
</html>
