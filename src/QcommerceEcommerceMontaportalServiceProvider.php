<?php

namespace Qubiqx\QcommerceEcommerceMontaportal;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use Qubiqx\QcommerceEcommerceCore\Models\Product;
use Qubiqx\QcommerceEcommerceMontaportal\Commands\PushOrdersToMontaportalCommand;
use Qubiqx\QcommerceEcommerceMontaportal\Commands\PushProductsToMontaportal;
use Qubiqx\QcommerceEcommerceMontaportal\Commands\SyncProductStockWithMontaportal;
use Qubiqx\QcommerceEcommerceMontaportal\Commands\UpdateOrdersToMontaportalCommand;
use Qubiqx\QcommerceEcommerceMontaportal\Commands\UpdateOrderTrackAndTraceFromMontaportalCommand;
use Qubiqx\QcommerceEcommerceMontaportal\Filament\Pages\Settings\MontaportalSettingsPage;
use Qubiqx\QcommerceEcommerceMontaportal\Filament\Resources\MontaportalProductResource;
use Qubiqx\QcommerceEcommerceMontaportal\Filament\Widgets\MontaportalFailedOrders;
use Qubiqx\QcommerceEcommerceMontaportal\Filament\Widgets\MontaportalOrderStats;
use Qubiqx\QcommerceEcommerceMontaportal\Livewire\Orders\ShowMontaportalOrder;
use Qubiqx\QcommerceEcommerceMontaportal\Livewire\Products\EditMontaportalProduct;
use Qubiqx\QcommerceEcommerceMontaportal\Models\MontaportalOrder;
use Qubiqx\QcommerceEcommerceMontaportal\Models\MontaportalProduct;
use Spatie\LaravelPackageTools\Package;

class QcommerceEcommerceMontaportalServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-ecommerce-montaportal';

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
            $schedule->command(PushProductsToMontaportal::class)->everyFiveMinutes();
            $schedule->command(SyncProductStockWithMontaportal::class)->everyFiveMinutes();
            $schedule->command(PushOrdersToMontaportalCommand::class)->everyFiveMinutes();
            $schedule->command(UpdateOrderTrackAndTraceFromMontaportalCommand::class)->everyFifteenMinutes();
            $schedule->command(UpdateOrdersToMontaportalCommand::class)->everyFifteenMinutes();
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
            ->name('qcommerce-ecommerce-montaportal')
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
