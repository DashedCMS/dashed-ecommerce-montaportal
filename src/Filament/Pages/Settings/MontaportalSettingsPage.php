<?php

namespace Dashed\DashedEcommerceMontaportal\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedEcommerceMontaportal\Classes\Montaportal;

class MontaportalSettingsPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Montaportal shop';

    protected string $view = 'dashed-core::settings.pages.default-settings';
    public array $data = [];

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

    public function form(Schema $schema): Schema
    {
        $sites = Sites::getSites();
        $tabGroups = [];

        $tabs = [];
        foreach ($sites as $site) {
            $schema = [
                TextEntry::make('label')
                    ->state("Montaportal voor {$site['name']}")
                    ->state('Activeer Montaportal om de bestellingen te versturen.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextEntry::make("Montaportal is " . (! Customsetting::get('montaportal_connected', $site['id'], 0) ? 'niet' : '') . ' geconnect')
                    ->state(Customsetting::get('montaportal_connection_error', $site['id'], ''))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("montaportal_username_{$site['id']}")
                    ->label('Gebruikersnaam')
                    ->maxLength(255),
                TextInput::make("montaportal_password_{$site['id']}")
                    ->label('Wachtwoord')
                    ->type('password')
                    ->maxLength(255),
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

        return $schema->schema($tabGroups)
            ->statePath('data');
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('montaportal_username', $this->form->getState()["montaportal_username_{$site['id']}"], $site['id']);
            Customsetting::set('montaportal_password', $this->form->getState()["montaportal_password_{$site['id']}"], $site['id']);
            Customsetting::set('montaportal_connected', Montaportal::isConnected($site['id']), $site['id']);
        }

        Notification::make()
            ->title('De Montaportal instellingen zijn opgeslagen')
            ->success()
            ->send();

        return redirect(MontaportalSettingsPage::getUrl());
    }
}
