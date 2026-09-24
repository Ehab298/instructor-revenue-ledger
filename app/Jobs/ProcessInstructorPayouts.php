<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessInstructorPayouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $instructorId,
    ) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            $instructor = User::query()
                ->whereKey($this->instructorId)
                ->lockForUpdate()
                ->first();

            if (! $instructor || $instructor->role !== 'instructor') {
                return;
            }
            if (Payout::query()->where('instructor_id', $instructor->id)->inFlight()->exists()) {
                return;
            }

            $outstanding = $instructor->balance;

            if ($outstanding <= 0) {
                return;
            }

            $payout = Payout::create([
                'instructor_id' => $instructor->id,
                'amount' => $outstanding,
                'status' => 'pending',
                'provider_reference' => (string) str()->uuid(),
            ]);

            ProcessPayout::dispatch($payout->id);
        });
    }
}
