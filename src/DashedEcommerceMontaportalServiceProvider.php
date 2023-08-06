<?php

namespace Dashed\DashedEcommerceMontaportal;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Dashed\DashedEcommerceCore\Models\Order;
use Dashed\DashedEcommerceCore\Models\Product;
use Dashed\DashedEcommerceMontaportal\Commands\PushOrdersToMontaportalCommand;
use Dashed\DashedEcommerceMontaportal\Commands\PushProductsToMontaportal;
use Dashed\DashedEcommerceMontaportal\Commands\SyncProductStockWithMontaportal;
use Dashed\DashedEcommerceMontaportal\Commands\UpdateOrdersToMontaportalCommand;
use Dashed\DashedEcommerceMontaportal\Commands\UpdateOrderTrackAndTraceFromMontaportalCommand;
use Dashed\DashedEcommerceMontaportal\Filament\Pages\Settings\MontaportalSettingsPage;
use Dashed\DashedEcommerceMontaportal\Filament\Resources\MontaportalProductResource;
use Dashed\DashedEcommerceMontaportal\Filament\Widgets\MontaportalFailedOrders;
use Dashed\DashedEcommerceMontaportal\Filament\Widgets\MontaportalOrderStats;
use Dashed\DashedEcommerceMontaportal\Livewire\Orders\ShowMontaportalOrder;
use Dashed\DashedEcommerceMontaportal\Livewire\Products\EditMontaportalProduct;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalOrder;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalProduct;
use Spatie\LaravelPackageTools\Package;

class DashedEcommerceMontaportalServiceProvider extends PluginServiceProvider
{
    public static string $name = 'dashed-ecommerce-montaportal';

    public function bootingPackage()
    {
        Livewire::component('show-montaportal-order', ShowMontaportalOrder::class);
        Livewire::component('edit-montaportal-product', EditMontaportalProduct::class);

        Order::addDynamicRelation('montaPortalOrder', function (Order $model) {
            return $model->hasOne(MontaportalOrder::class);
        });

        Product::addDynamicRelation('montaportalProduct', function (Product $model) {
            return $model->hasOne(MontaportalProduct::class);
        });

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(PushProductsToMontaportal::class)
                ->everyFiveMinutes()
                ->withoutOverlapping();
            $schedule->command(SyncProductStockWithMontaportal::class)
                ->everyFiveMinutes()
                ->withoutOverlapping();
            $schedule->command(PushOrdersToMontaportalCommand::class)
                ->everyFiveMinutes()
                ->withoutOverlapping();
            $schedule->command(UpdateOrderTrackAndTraceFromMontaportalCommand::class)
                ->everyFifteenMinutes()
                ->withoutOverlapping();
            $schedule->command(UpdateOrdersToMontaportalCommand::class)
                ->everyFifteenMinutes()
                ->withoutOverlapping();
        });
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'montaportal' => [
                    'name' => 'Montaportal',
                    'description' => 'Koppel Montaportal aan je bestellingen',
                    'icon' => 'archive',
                    'page' => MontaportalSettingsPage::class,
                ],
            ])
        );

        ecommerce()->widgets(
            'orders',
            array_merge(ecommerce()->widgets('orders'), [
                'show-montaportal-order' => [
                    'name' => 'show-montaportal-order',
                    'width' => 'sidebar',
                ],
            ])
        );

        $package
            ->name('dashed-ecommerce-montaportal')
            ->hasViews()
            ->hasCommands([
                PushProductsToMontaportal::class,
                SyncProductStockWithMontaportal::class,
                PushOrdersToMontaportalCommand::class,
                UpdateOrdersToMontaportalCommand::class,
                UpdateOrderTrackAndTraceFromMontaportalCommand::class,
            ]);
    }

    protected function getResources(): array
    {
        return array_merge(parent::getResources(), [
            MontaportalProductResource::class,
        ]);
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            MontaportalSettingsPage::class,
        ]);
    }

    protected function getWidgets(): array
    {
        return array_merge(parent::getWidgets(), [
            MontaportalOrderStats::class,
            MontaportalFailedOrders::class,
        ]);
    }
}
