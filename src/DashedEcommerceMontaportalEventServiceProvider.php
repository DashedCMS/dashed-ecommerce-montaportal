<?php

namespace Dashed\DashedEcommerceMontaportal;

use Dashed\DashedEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;
use Dashed\DashedEcommerceMontaportal\Listeners\MarkOrderAsPushableListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class DashedEcommerceMontaportalEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderMarkedAsPaidEvent::class => [
            MarkOrderAsPushableListener::class,
        ],
    ];
}
