<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Listeners;

use Qubiqx\QcommerceEcommerceMontaportal\Classes\Montaportal;
use Qubiqx\QcommerceEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;

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
