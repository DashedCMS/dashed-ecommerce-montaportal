<?php

namespace Dashed\DashedEcommerceMontaportal\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalOrder;

class MontaportalOrderStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1000000;

    protected function getCards(): array
    {
        return [
            StatsOverviewWidget\Stat::make('Aantal bestellingen naar Montaportal', MontaportalOrder::where('pushed_to_montaportal', 1)->count()),
            StatsOverviewWidget\Stat::make('Aantal bestellingen in de wacht', MontaportalOrder::where('pushed_to_montaportal', 0)->count()),
            StatsOverviewWidget\Stat::make('Aantal bestellingen gefaald', MontaportalOrder::where('pushed_to_montaportal', 2)->count()),
        ];
    }
}
