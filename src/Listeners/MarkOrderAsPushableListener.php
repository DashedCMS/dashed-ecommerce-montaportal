<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Listeners;

use Qubiqx\QcommerceEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;
use Qubiqx\QcommerceEcommerceEfulfillmentshop\Classes\EfulfillmentShop;
use Qubiqx\QcommerceEcommerceEfulfillmentshop\Models\EfulfillmentshopOrder;

class MarkOrderAsPushableListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(OrderMarkedAsPaidEvent $event)
    {
        if (EfulfillmentShop::isConnected($event->order->site_id)) {
            $eshopOrder = new EfulfillmentshopOrder();
            $eshopOrder->order_id = $event->order->id;
            $eshopOrder->save();
        }
    }
}
