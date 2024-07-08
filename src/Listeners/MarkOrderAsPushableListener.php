<?php

namespace Dashed\DashedEcommerceMontaportal\Listeners;

use Dashed\DashedEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;
use Dashed\DashedEcommerceMontaportal\Classes\Montaportal;

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
        if (Montaportal::isConnected($event->order->site_id) && $event->order->eligibleForFulfillmentProvider('montaportal')) {
            $event->order->montaPortalOrder()->create([]);
        }
    }
}
