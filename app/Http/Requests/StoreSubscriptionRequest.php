<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'student')],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', Rule::exists('courses', 'id')],
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
        ];
    }
}
