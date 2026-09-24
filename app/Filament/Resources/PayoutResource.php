<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutResource\Pages;
use App\Models\Payout;
use App\Support\Money;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?string $navigationLabel = 'Payout history';

    protected static ?string $modelLabel = 'payout';

    protected static ?string $pluralModelLabel = 'payout history';

    public static function canCreate(): bool
    {
        return false; // payouts are created by payouts:run only
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instructor.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->formatStateUsing(fn ($state) => Money::format((int) $state))
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'timeout' => 'warning',
                        'processing' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('provider_reference')
                    ->label('Provider reference')
                    ->copyable()
                    ->limit(24),

                TextColumn::make('attempts')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Scheduled at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'timeout' => 'Timeout',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
        ];
    }
}
