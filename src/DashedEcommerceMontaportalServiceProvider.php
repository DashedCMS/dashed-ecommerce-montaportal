<?php

namespace Dashed\DashedEcommerceMontaportal;

use Livewire\Livewire;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedEcommerceCore\Models\Order;
use Dashed\DashedEcommerceCore\Models\Product;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedEcommerceMontaportal\Classes\Montaportal;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalOrder;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalProduct;
use Dashed\DashedEcommerceMontaportal\Commands\DeleteMontaportalProducts;
use Dashed\DashedEcommerceMontaportal\Commands\PushProductsToMontaportal;
use Dashed\DashedEcommerceMontaportal\Livewire\Orders\ShowMontaportalOrder;
use Dashed\DashedEcommerceMontaportal\Commands\PushOrdersToMontaportalCommand;
use Dashed\DashedEcommerceMontaportal\Commands\SyncProductStockWithMontaportal;
use Dashed\DashedEcommerceMontaportal\Livewire\Products\EditMontaportalProduct;
use Dashed\DashedEcommerceMontaportal\Commands\SyncUnconnectedMontaportalOrders;
use Dashed\DashedEcommerceMontaportal\Commands\UpdateOrdersToMontaportalCommand;
use Dashed\DashedEcommerceMontaportal\Filament\Pages\Settings\MontaportalSettingsPage;
use Dashed\DashedEcommerceMontaportal\Commands\UpdateOrderTrackAndTraceFromMontaportalCommand;

class DashedEcommerceMontaportalServiceProvider extends PackageServiceProvider
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
            $schedule->command(DeleteMontaportalProducts::class)
                ->everyFiveMinutes()
                ->withoutOverlapping();
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
            $schedule->command(SyncUnconnectedMontaportalOrders::class)
                ->everyMinute()
                ->withoutOverlapping();
        });
        Gate::policy(\Dashed\DashedEcommerceMontaportal\Models\MontaportalProduct::class, \Dashed\DashedEcommerceMontaportal\Policies\MontaportalProductPolicy::class);

        cms()->registerRolePermissions('Integraties', [
            'view_montaportal_product' => 'Montaportal producten bekijken',
            'edit_montaportal_product' => 'Montaportal producten bewerken',
            'delete_montaportal_product' => 'Montaportal producten verwijderen',
        ]);

        cms()->registerResourceDocs(
            resource: \Dashed\DashedEcommerceMontaportal\Filament\Resources\MontaportalProductResource::class,
            title: 'Montaportal producten',
            intro: 'Beheer de koppeling tussen je producten en Montaportal voor fulfillment. Per product kun je met een simpele schakelaar bepalen of de voorraad gesynchroniseerd moet worden.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Zien welke producten aan Montaportal gekoppeld zijn.
- Per product de voorraad-sync aan of uit zetten.
- De status van de koppeling per product volgen.
- Snel schakelen als je een product tijdelijk uit fulfillment wilt halen.
MARKDOWN,
                ],
                [
                    'heading' => 'Wat is er bijzonder?',
                    'body' => 'Dit overzicht is bewust beperkt aanpasbaar. Alleen de voorraad-sync schakelaar kun je bedienen, en verwijderen is niet mogelijk om de koppeling veilig te houden.',
                ],
            ],
            tips: [
                'Zet de voorraad-sync alleen uit als je echt weet waarom.',
                'Pas de productgegevens zelf aan in het reguliere productenscherm.',
            ],
        );

        cms()->registerSettingsDocs(
            page: \Dashed\DashedEcommerceMontaportal\Filament\Pages\Settings\MontaportalSettingsPage::class,
            title: 'Montaportal instellingen',
            intro: 'Koppel de webshop met Montaportal, zodat bestellingen automatisch naar het magazijn van je fulfillment partner worden doorgezet voor inpakken en verzenden. Per site vul je hier de inloggegevens en de identifier in die je van Montaportal hebt gekregen.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => <<<MARKDOWN
Op deze pagina koppel je de webshop aan je Montaportal account. Je vult drie gegevens in:

1. De gebruikersnaam van je Montaportal account.
2. Het bijbehorende wachtwoord.
3. De origin identifier die Montaportal aan jouw webshop heeft gekoppeld.

Zodra de koppeling staat, kan de webshop bestellingen doorgeven aan het magazijn.
MARKDOWN,
                ],
                [
                    'heading' => 'Hoe zet je dit op?',
                    'body' => <<<MARKDOWN
1. Vraag bij Montaportal de inloggegevens op die specifiek voor de webshop koppeling bedoeld zijn. Dit zijn meestal andere gegevens dan waarmee je zelf op het portaal inlogt.
2. Vraag ook de origin identifier op. Weet je niet welke waarde hier moet staan? Neem dan contact op met de support van Montaportal, zij kunnen dit aanleveren.
3. Vul de gebruikersnaam, het wachtwoord en de origin in op deze pagina.
4. Sla de instellingen op.
5. Plaats een proefbestelling om te controleren of deze in Montaportal aankomt.
MARKDOWN,
                ],
            ],
            fields: [
                'Gebruikersnaam' => 'De gebruikersnaam die je van Montaportal hebt gekregen voor de webshop koppeling.',
                'Wachtwoord' => 'Het wachtwoord bij bovenstaande gebruikersnaam. Bewaar dit veilig en deel het niet met derden.',
                'Origin identifier' => 'Identifier die door Montaportal wordt aangeleverd tijdens de integratie. Vraag dit op bij Montaportal support als je het niet weet.',
            ],
            tips: [
                'Gebruik altijd de credentials die Montaportal speciaal voor de koppeling aanmaakt, niet je eigen inloggegevens van het portaal.',
                'Test de koppeling met een kleine proefbestelling voordat je live gaat. Zo zie je meteen of de gegevens kloppen en of de bestelling correct in het magazijn binnenkomt.',
                'Bewaar het wachtwoord op een veilige plek (bijvoorbeeld in een wachtwoordkluis), zodat je het bij een wijziging snel terug kunt vinden.',
            ],
        );
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->registerSettingsPage(MontaportalSettingsPage::class, 'Montaportal', 'archive-box', 'Koppel Montaportal aan je bestellingen');

        ecommerce()->widgets(
            'orders',
            array_merge(ecommerce()->widgets('orders'), [
                'show-montaportal-order' => [
                    'name' => 'show-montaportal-order',
                    'width' => 'sidebar',
                ],
            ])
        );

        ecommerce()->builder(
            'fulfillmentProviders',
            array_merge(ecommerce()->builder('fulfillmentProviders'), [
                'montaportal' => [
                    'name' => 'MontaPortal',
                    'class' => Montaportal::class,
                ],
            ])
        );

        $package
            ->name('dashed-ecommerce-montaportal')
            ->hasViews()
            ->hasCommands([
                DeleteMontaportalProducts::class,
                PushProductsToMontaportal::class,
                SyncProductStockWithMontaportal::class,
                PushOrdersToMontaportalCommand::class,
                UpdateOrdersToMontaportalCommand::class,
                UpdateOrderTrackAndTraceFromMontaportalCommand::class,
                SyncUnconnectedMontaportalOrders::class,
            ]);

        cms()->builder('plugins', [
            new DashedEcommerceMontaportalPlugin(),
        ]);
    }
}
