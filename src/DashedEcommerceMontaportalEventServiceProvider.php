<?php

namespace Dashed\DashedEcommerceMontaportal;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Dashed\DashedEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;
use Dashed\DashedEcommerceMontaportal\Listeners\MarkOrderAsPushableListener;

class DashedEcommerceMontaportalEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderMarkedAsPaidEvent::class => [
            MarkOrderAsPushableListener::class,
        ],
    ];
}
