<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorResource\Pages;
use App\Filament\Resources\InstructorResource\RelationManagers;
use App\Models\User;
use App\Support\Money;
use Filament\Resources\Resource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstructorResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $navigationLabel = 'Instructors';

    protected static ?string $modelLabel = 'instructor';

    protected static ?string $pluralModelLabel = 'instructors';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'instructor');
    }

    public static function canCreate(): bool
    {
        return false; // read-only screen
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('total_earned')
                    ->label('Total earned')
                    ->formatStateUsing(fn ($state) => Money::format((int) $state))
                    ->sortable(false),

                TextColumn::make('total_paid')
                    ->label('Total paid')
                    ->formatStateUsing(fn ($state) => Money::format((int) $state))
                    ->sortable(false),

                TextColumn::make('balance')
                    ->label('Outstanding balance')
                    ->formatStateUsing(fn ($state) => Money::format((int) $state))
                    ->badge()
                    ->color(fn ($state) => (int) $state > 0 ? 'warning' : 'success')
                    ->sortable(false),
            ])
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->defaultSort('name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Instructor balance')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('total_earned')
                            ->label('Total earned')
                            ->formatStateUsing(fn ($state) => Money::format((int) $state)),

                        TextEntry::make('total_paid')
                            ->label('Total paid out')
                            ->formatStateUsing(fn ($state) => Money::format((int) $state)),

                        TextEntry::make('balance')
                            ->label('Outstanding balance')
                            ->formatStateUsing(fn ($state) => Money::format((int) $state))
                            ->color(fn ($state) => (int) $state > 0 ? 'warning' : 'success'),
                    ]),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PayoutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructors::route('/'),
            'view' => Pages\ViewInstructor::route('/{record}'),
        ];
    }
}
