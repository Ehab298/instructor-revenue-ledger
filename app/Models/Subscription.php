<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'plan',
        'amount_paid',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'plan' => SubscriptionPlan::class,
            'amount_paid' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('ends_at', '>', now());
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_subscription');
    }

    public function ledgerTransactions(): MorphMany
    {
        return $this->morphMany(LedgerTransaction::class, 'reference');
    }
}
