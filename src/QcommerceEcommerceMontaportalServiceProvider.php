<?php

namespace Qubiqx\QcommerceEcommerceMontaportal;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use Qubiqx\QcommerceEcommerceCore\Models\Product;
use Qubiqx\QcommerceEcommerceMontaportal\Commands\PushOrdersToMontaportalCommand;
use Qubiqx\QcommerceEcommerceMontaportal\Commands\PushProductsToMontaportal;
use Qubiqx\QcommerceEcommerceMontaportal\Commands\SyncProductStockWithMontaportal;
use Qubiqx\QcommerceEcommerceMontaportal\Commands\UpdateOrdersToMontaportalCommand;
use Qubiqx\QcommerceEcommerceMontaportal\Filament\Pages\Settings\MontaportalSettingsPage;
use Qubiqx\QcommerceEcommerceMontaportal\Models\MontaportalOrder;
use Qubiqx\QcommerceEcommerceMontaportal\Models\MontaportalProduct;
use Spatie\LaravelPackageTools\Package;

class QcommerceEcommerceMontaportalServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-ecommerce-montaportal';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(PushProductsToMontaportal::class)->everyFiveMinutes();
            $schedule->command(SyncProductStockWithMontaportal::class)->everyFiveMinutes();
            $schedule->command(PushOrdersToMontaportalCommand::class)->everyFiveMinutes();
            $schedule->command(UpdateOrdersToMontaportalCommand::class)->everyFifteenMinutes();
        });

        Order::addDynamicRelation('montaPortalOrder', function (Order $model) {
            return $model->hasOne(MontaportalOrder::class);
        });
        Product::addDynamicRelation('montaportalProduct', function (Product $model) {
            return $model->hasOne(MontaportalProduct::class);
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

        $package
            ->name('qcommerce-ecommerce-montaportal')
            ->hasViews()
            ->hasCommands([
                PushProductsToMontaportal::class,
                SyncProductStockWithMontaportal::class,
                PushOrdersToMontaportalCommand::class,
                UpdateOrdersToMontaportalCommand::class,
            ]);
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            MontaportalSettingsPage::class,
        ]);
    }
}
