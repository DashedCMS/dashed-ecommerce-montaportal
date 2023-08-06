<?php

namespace Dashed\DashedEcommerceMontaportal\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalOrder;

class MontaportalOrderStats extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Aantal bestellingen naar Montaportal', MontaportalOrder::where('pushed_to_montaportal', 1)->count()),
            Card::make('Aantal bestellingen in de wacht', MontaportalOrder::where('pushed_to_montaportal', 0)->count()),
            Card::make('Aantal bestellingen gefaald', MontaportalOrder::where('pushed_to_montaportal', 2)->count()),
        ];
    }
}
