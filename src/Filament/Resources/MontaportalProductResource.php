<?php

namespace Dashed\DashedEcommerceMontaportal\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalProduct;
use Dashed\DashedEcommerceMontaportal\Filament\Resources\MontaportalProductResource\Pages\EditMontaportalProduct;
use Dashed\DashedEcommerceMontaportal\Filament\Resources\MontaportalProductResource\Pages\ListMontaportalProducts;

class MontaportalProductResource extends Resource
{
    protected static ?string $model = MontaportalProduct::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string|UnitEnum|null $navigationGroup = 'Producten';
    protected static ?string $navigationLabel = 'Montaportal producten';
    protected static ?string $label = 'Montaportal product';
    protected static ?string $pluralLabel = 'Montaportal producten';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()->columnSpanFull()
                    ->schema([
                        TextEntry::make('')
                            ->state(fn ($record) => 'Bewerk instellingen voor Montaportal voor product ' . $record->product->name),
                        Toggle::make('sync_stock')
                            ->label('Sync voorraad'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),
                BooleanColumn::make('sync_stock')
                    ->label('Sync voorraad'),

            ])
            ->filters([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMontaportalProducts::route('/'),
            'edit' => EditMontaportalProduct::route('/{record}/edit'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
