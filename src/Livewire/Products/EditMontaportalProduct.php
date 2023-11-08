<?php

namespace Dashed\DashedEcommerceMontaportal\Livewire\Products;

use Livewire\Component;

class EditMontaportalProduct extends Component
{
    public $product;

    public function mount($product)
    {
        $this->product = $product;
    }

    public function render()
    {
        return view('dashed-ecommerce-montaportal::products.components.show-montaportal-product');
    }

    public function submit()
    {
        if (! $this->product->montaPortalproduct) {
            $this->dispatch('notify', [
                'status' => 'error',
                'message' => 'De bestelling mag niet naar Montaportal gepushed worden.',
            ]);
        } elseif ($this->product->montaPortalproduct->pushed_to_montaportal == 1) {
            $this->dispatch('notify', [
                'status' => 'error',
                'message' => 'De bestelling is al naar Montaportal gepushed.',
            ]);
        } elseif ($this->product->montaPortalproduct->pushed_to_montaportal == 0) {
            $this->dispatch('notify', [
                'status' => 'error',
                'message' => 'De bestelling wordt al naar Montaportal gepushed.',
            ]);
        }

        $this->product->montaPortalproduct->pushed_to_montaportal = 0;
        $this->product->montaPortalproduct->save();

        $this->dispatch('refreshPage');
        $this->dispatch('notify', [
            'status' => 'success',
            'message' => 'De bestelling wordt binnen enkele minuten opnieuw naar Montaportal gepushed.',
        ]);
    }
}
