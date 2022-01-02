<?php

namespace Qubiqx\QcommerceEcommerceMontaportal;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceEcommerceMontaportal\Filament\Pages\Settings\MontaportalSettingsPage;
use Spatie\LaravelPackageTools\Package;

class QcommerceEcommerceMontaportalServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-ecommerce-montaportal';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
//            $schedule->command(PushProductsToEfulfillmentShopCommand::class)->everyFiveMinutes();
//            $schedule->command(PushOrdersToEfulfillmentShopCommand::class)->everyFiveMinutes();
//            $schedule->command(UpdateOrdersFromEfulfillmentShopCommand::class)->everyFiveMinutes();
        });

//        Product::addDynamicRelation('efulfillmentShopProduct', function (Product $model) {
//            return $model->hasOne(EfulfillmentshopProduct::class);
//        });
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
//                PushOrdersToEfulfillmentShopCommand::class,
//                UpdateOrdersFromEfulfillmentShopCommand::class,
//                PushProductsToEfulfillmentShopCommand::class,
            ]);
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            MontaportalSettingsPage::class,
        ]);
    }
}
