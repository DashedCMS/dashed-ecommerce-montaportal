<?php

namespace Dashed\DashedEcommerceMontaportal\Listeners;

use Dashed\DashedEcommerceCore\Models\OrderLog;
use Dashed\DashedEcommerceMontaportal\Classes\Montaportal;
use Dashed\DashedEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;

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
            $orderLog = new OrderLog();
            $orderLog->order_id = $this->id;
            $orderLog->user_id = null;
            $orderLog->tag = 'system.note.created';
            $orderLog->note = 'Montaportal order created';
            $orderLog->save();
        } elseif (! Montaportal::isConnected($event->order->site_id)) {
            $orderLog = new OrderLog();
            $orderLog->order_id = $this->id;
            $orderLog->user_id = null;
            $orderLog->tag = 'system.note.created';
            $orderLog->note = 'Montaportal order not created, site not connected';
            $orderLog->save();
        } elseif (! $event->order->eligibleForFulfillmentProvider('montaportal')) {
            $orderLog = new OrderLog();
            $orderLog->order_id = $this->id;
            $orderLog->user_id = null;
            $orderLog->tag = 'system.note.created';
            $orderLog->note = 'Montaportal order not created, order not eligible for Montaportal';
            $orderLog->save();
        }
    }
}
