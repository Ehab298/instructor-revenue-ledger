<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'amount',
        'status',
        'provider_reference',
        'attempts',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer', // piastres
            'attempts' => 'integer',
        ];
    }

    /**
     * Payouts that might still move money — used to avoid scheduling a
     * second payout for an instructor while one is unresolved.
     */
    public function scopeInFlight(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'processing', 'timeout']);
    }

    /**
     * Atomically take ownership of a pending payout. Returns false when
     * another worker (or a retried copy of this job) already owns it —
     * the caller must do nothing in that case.
     */
    public function claim(): bool
    {
        $claimed = static::query()
            ->whereKey($this->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'processing',
                'attempts' => DB::raw('attempts + 1'),
            ]);

        if ($claimed) {
            $this->refresh();
        }

        return (bool) $claimed;
    }

    /**
     * Atomically take ownership of a timed-out payout for status resolution.
     */
    public function claimForResolution(): bool
    {
        $claimed = static::query()
            ->whereKey($this->id)
            ->where('status', 'timeout')
            ->update(['status' => 'processing']);

        if ($claimed) {
            $this->refresh();
        }

        return (bool) $claimed;
    }

    public function markTimedOut(): bool
    {
        return (bool) static::query()
            ->whereKey($this->id)
            ->where('status', 'processing')
            ->update(['status' => 'timeout']);
    }

    /**
     * Finalize as paid: guarded transition plus exactly one deducting
     * ledger row, committed together. Only the worker that wins the
     * transition writes the ledger — this is what makes double payment
     * impossible.
     */
    public function markSucceeded(): bool
    {
        return DB::transaction(function (): bool {
            $finalized = static::query()
                ->whereKey($this->id)
                ->where('status', 'processing')
                ->update(['status' => 'success']);

            if (! $finalized) {
                return false;
            }

            $this->ledgerTransactions()->create([
                'instructor_id' => $this->instructor_id,
                'amount' => $this->amount,
                'type' => 'payout',
                'status' => 'Completed',
            ]);

            return true;
        });
    }

    /**
     * Finalize as permanently failed: the provider confirmed no money
     * moved, so nothing is written to the ledger and the amount stays
     * outstanding for a future run.
     */
    public function markFailed(): bool
    {
        return (bool) static::query()
            ->whereKey($this->id)
            ->where('status', 'processing')
            ->update(['status' => 'failed']);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function ledgerTransactions(): MorphMany
    {
        return $this->morphMany(LedgerTransaction::class, 'reference');
    }
}
