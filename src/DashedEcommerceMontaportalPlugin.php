<?php

namespace Dashed\DashedEcommerceMontaportal;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Dashed\DashedEcommerceMontaportal\Filament\Widgets\MontaportalOrderStats;
use Dashed\DashedEcommerceMontaportal\Filament\Widgets\MontaportalFailedOrders;
use Dashed\DashedEcommerceMontaportal\Filament\Resources\MontaportalProductResource;
use Dashed\DashedEcommerceMontaportal\Filament\Pages\Settings\MontaportalSettingsPage;

class DashedEcommerceMontaportalPlugin implements Plugin
{
    public function getId(): string
    {
        return 'dashed-ecommerce-montaportal';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                MontaportalSettingsPage::class,
            ])
            ->resources([
                MontaportalProductResource::class,
            ])
            ->widgets([
                MontaportalOrderStats::class,
                MontaportalFailedOrders::class,
            ]);
    }

    public function boot(Panel $panel): void
    {

    }
}
