<?php

namespace Dashed\DashedEcommerceMontaportal\Livewire\Orders;

use Filament\Notifications\Notification;
use Livewire\Component;

class ShowMontaportalOrder extends Component
{
    public $order;

    public function mount($order)
    {
        $this->order = $order;
    }

    public function render()
    {
        return view('dashed-ecommerce-montaportal::orders.components.show-montaportal-order');
    }

    public function submit()
    {
        if (! $this->order->montaPortalOrder) {
            Notification::make()
                ->danger()
                ->title('De bestelling mag niet naar Montaportal gepushed worden.')
                ->send();
            return;
        } elseif ($this->order->montaPortalOrder->pushed_to_montaportal == 1) {
            Notification::make()
                ->danger()
                ->title('De bestelling is al naar Montaportal gepushed.')
                ->send();
            return;
        } elseif ($this->order->montaPortalOrder->pushed_to_montaportal == 0) {
            Notification::make()
                ->danger()
                ->title('De bestelling wordt al naar Montaportal gepushed.')
                ->send();
            return;
        }

        $this->order->montaPortalOrder->pushed_to_montaportal = 0;
        $this->order->montaPortalOrder->save();

        $this->dispatch('refreshPage');
        Notification::make()
            ->success()
            ->title('De bestelling wordt binnen enkele minuten opnieuw naar Montaportal gepushed.')
            ->send();
    }

    public function createMontaportalOrder()
    {
        if ($this->order->montaPortalOrder) {
            Notification::make()
                ->danger()
                ->title('De bestelling is al aan Montaportal gekoppeld.')
                ->send();
            return;
        }

        $this->order->montaPortalOrder()->create();

        $this->dispatch('refreshPage');
        Notification::make()
            ->success()
            ->title('De bestelling wordt binnen enkele minuten naar Montaportal gepushed.')
            ->send();
    }

    public function deleteMontaportalOrder()
    {
        if (!$this->order->montaPortalOrder) {
            Notification::make()
                ->danger()
                ->title('De bestelling is al uit Montaportal verwijderd.')
                ->send();
            return;
        }

        if($this->order->montaPortalOrder->pushed_to_montaportal == 1){
            Notification::make()
                ->danger()
                ->title('De bestelling is al naar Montaportal gepushed.')
                ->send();
            return;
        }

        $this->order->montaPortalOrder()->delete();

        $this->dispatch('refreshPage');
        Notification::make()
            ->success()
            ->title('De bestelling is uit Montaportal verwijderd.')
            ->send();
    }
}
