<?php

namespace App\Filament\Widgets;

use App\Models\LedgerTransaction;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayoutStats extends BaseWidget
{
    protected function getStats(): array
    {
        $earned = (int) LedgerTransaction::query()->where('type', 'earning')->sum('amount');
        $refunded = (int) LedgerTransaction::query()->where('type', 'refund')->sum('amount');
        $paid = (int) LedgerTransaction::query()->where('type', 'payout')->sum('amount');

        $outstanding = max(0, ($earned - $refunded) - $paid);

        return [
            Stat::make('Instructor earnings', Money::format($earned - $refunded))
                ->description('Total 70% pool recorded')
                ->color('info'),

            Stat::make('Paid out', Money::format($paid))
                ->description('Confirmed payouts')
                ->color('success'),

            Stat::make('Outstanding balance', Money::format($outstanding))
                ->description($outstanding > 0 ? 'Owed to instructors' : 'All settled')
                ->color($outstanding > 0 ? 'warning' : 'success'),
        ];
    }
}
