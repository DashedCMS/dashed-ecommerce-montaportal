<?php

namespace Dashed\DashedEcommerceMontaportal\Filament\Pages\Settings;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceMontaportal\Classes\Montaportal;

class MontaportalSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Montaportal shop';

    protected static string $view = 'dashed-core::settings.pages.default-settings';

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["montaportal_username_{$site['id']}"] = Customsetting::get('montaportal_username', $site['id']);
            $formData["montaportal_password_{$site['id']}"] = Customsetting::get('montaportal_password', $site['id']);
            $formData["montaportal_connected_{$site['id']}"] = Customsetting::get('montaportal_connected', $site['id'], 0) ? true : false;
        }

        $this->form->fill($formData);
    }

    protected function getFormSchema(): array
    {
        $sites = Sites::getSites();
        $tabGroups = [];

        $tabs = [];
        foreach ($sites as $site) {
            $schema = [
                Placeholder::make('label')
                    ->label("Montaportal voor {$site['name']}")
                    ->content('Activeer Montaportal om de bestellingen te versturen.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Placeholder::make('label')
                    ->label("Montaportal is " . (! Customsetting::get('montaportal_connected', $site['id'], 0) ? 'niet' : '') . ' geconnect')
                    ->content(Customsetting::get('montaportal_connection_error', $site['id'], ''))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("montaportal_username_{$site['id']}")
                    ->label('Gebruikersnaam')
                    ->rules([
                        'max:255',
                    ]),
                TextInput::make("montaportal_password_{$site['id']}")
                    ->label('Wachtwoord')
                    ->type('password')
                    ->rules([
                        'max:255',
                    ]),
            ];

            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema($schema)
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ]);
        }
        $tabGroups[] = Tabs::make('Sites')
            ->tabs($tabs);

        return $tabGroups;
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('montaportal_username', $this->form->getState()["montaportal_username_{$site['id']}"], $site['id']);
            Customsetting::set('montaportal_password', $this->form->getState()["montaportal_password_{$site['id']}"], $site['id']);
            Customsetting::set('montaportal_connected', Montaportal::isConnected($site['id']), $site['id']);
        }

        $this->notify('success', 'De Montaportal instellingen zijn opgeslagen');

        return redirect(MontaportalSettingsPage::getUrl());
    }
}
