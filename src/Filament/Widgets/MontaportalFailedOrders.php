<?php

namespace Dashed\DashedEcommerceMontaportal\Filament\Widgets;

use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Relations\Relation;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalOrder;

class MontaportalFailedOrders extends BaseWidget
{
    protected int|string|array $columnSpan = 2;

    public function getTableHeading(): string
    {
        return 'Bestellingen die niet naar Monta zijn gestuurd';
    }

    protected function getTableQuery(): Builder|Relation
    {
        return MontaportalOrder::where('pushed_to_montaportal', 2);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('order.name')
                ->label('Naam'),
            TextColumn::make('order.email')
                ->label('Email'),
            TextColumn::make('created_at')
                ->label('Aangemaakt op'),
            TextColumn::make('error')
                ->label('Fout'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view_order')
                ->label('Bekijk bestelling')
                ->button()
                ->url(fn ($record) => route('filament.dashed.resources.orders.view', [$record->order_id])),
        ];
    }
}
