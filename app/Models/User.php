<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'instructor_id');
    }

    public function ledgerTransactions(): HasMany
    {
        return $this->hasMany(LedgerTransaction::class, 'instructor_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'student_id');
    }

    public function getBalanceAttribute(): int
    {
        return (int) $this->ledgerTransactions()->sum('amount');
    }
}
