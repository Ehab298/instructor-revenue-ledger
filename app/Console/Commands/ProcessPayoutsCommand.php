<?php

namespace App\Console\Commands;

use App\Jobs\ProcessInstructorPayouts;
use App\Models\User;
use Illuminate\Console\Command;

class ProcessPayoutsCommand extends Command
{
    protected $signature = 'payouts:run
        {--instructor=* : Restrict to the given instructor ID(s)}';

    protected $description = 'Schedule payouts for every instructor with an outstanding balance';

    public function handle(): int
    {
        $instructors = User::query()
            ->where('role', 'instructor')
            ->orderBy('id')
            ->when($this->option('instructor'), fn ($query, $ids) => $query->whereKey($ids))
            ->pluck('id');

        foreach ($instructors as $instructorId) {
            ProcessInstructorPayouts::dispatch($instructorId);
        }

        $this->info("Scheduled payout processing for {$instructors->count()} instructor(s).");

        return self::SUCCESS;
    }
}
