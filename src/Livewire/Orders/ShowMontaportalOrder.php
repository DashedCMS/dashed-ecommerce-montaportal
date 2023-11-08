<?php

namespace Dashed\DashedEcommerceMontaportal\Livewire\Orders;

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
            $this->dispatch('notify', [
                'status' => 'error',
                'message' => 'De bestelling mag niet naar Montaportal gepushed worden.',
            ]);
        } elseif ($this->order->montaPortalOrder->pushed_to_montaportal == 1) {
            $this->dispatch('notify', [
                'status' => 'error',
                'message' => 'De bestelling is al naar Montaportal gepushed.',
            ]);
        } elseif ($this->order->montaPortalOrder->pushed_to_montaportal == 0) {
            $this->dispatch('notify', [
                'status' => 'error',
                'message' => 'De bestelling wordt al naar Montaportal gepushed.',
            ]);
        }

        $this->order->montaPortalOrder->pushed_to_montaportal = 0;
        $this->order->montaPortalOrder->save();

        $this->dispatch('refreshPage');
        $this->dispatch('notify', [
            'status' => 'success',
            'message' => 'De bestelling wordt binnen enkele minuten opnieuw naar Montaportal gepushed.',
        ]);
    }
}
