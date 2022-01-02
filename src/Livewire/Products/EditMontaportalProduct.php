<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Livewire\Products;

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
        return view('qcommerce-ecommerce-montaportal::products.components.show-montaportal-product');
    }

    public function submit()
    {
        if (!$this->product->montaPortalproduct) {
            $this->emit('notify', [
                'status' => 'error',
                'message' => 'De bestelling mag niet naar Montaportal gepushed worden.'
            ]);
        } elseif ($this->product->montaPortalproduct->pushed_to_montaportal == 1) {
            $this->emit('notify', [
                'status' => 'error',
                'message' => 'De bestelling is al naar Montaportal gepushed.'
            ]);
        } elseif ($this->product->montaPortalproduct->pushed_to_montaportal == 0) {
            $this->emit('notify', [
                'status' => 'error',
                'message' => 'De bestelling wordt al naar Montaportal gepushed.'
            ]);
        }

        $this->product->montaPortalproduct->pushed_to_montaportal = 0;
        $this->product->montaPortalproduct->save();

        $this->emit('refreshPage');
        $this->emit('notify', [
            'status' => 'success',
            'message' => 'De bestelling wordt binnen enkele minuten opnieuw naar Montaportal gepushed.'
        ]);
    }
}
