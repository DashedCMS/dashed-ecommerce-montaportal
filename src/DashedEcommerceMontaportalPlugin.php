<?php

namespace Dashed\DashedEcommerceMontaportal;

use Dashed\DashedEcommerceMontaportal\Filament\Pages\Settings\MontaportalSettingsPage;
use Dashed\DashedEcommerceMontaportal\Filament\Resources\MontaportalProductResource;
use Dashed\DashedEcommerceMontaportal\Filament\Widgets\MontaportalFailedOrders;
use Dashed\DashedEcommerceMontaportal\Filament\Widgets\MontaportalOrderStats;
use Filament\Contracts\Plugin;
use Filament\Panel;

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
