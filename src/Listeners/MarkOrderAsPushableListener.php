<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Listeners;

use Qubiqx\QcommerceEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;
use Qubiqx\QcommerceEcommerceEfulfillmentshop\Classes\EfulfillmentShop;
use Qubiqx\QcommerceEcommerceEfulfillmentshop\Models\EfulfillmentshopOrder;
use Qubiqx\QcommerceEcommerceMontaportal\Classes\Montaportal;

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
        if (Montaportal::isConnected($event->order->site_id)) {
            $event->order->montaPortalOrder()->create([]);
        }
    }
}
