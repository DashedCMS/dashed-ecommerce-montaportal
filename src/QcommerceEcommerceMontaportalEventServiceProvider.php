<?php

namespace Qubiqx\QcommerceEcommerceMontaportal;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Qubiqx\QcommerceEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;
use Qubiqx\QcommerceEcommerceCore\Events\Products\ProductCreatedEvent;
use Qubiqx\QcommerceEcommerceMontaportal\Listeners\MarkOrderAsPushableListener;
use Qubiqx\QcommerceEcommerceMontaportal\Listeners\MarkProductAsPushableListener;

class QcommerceEcommerceMontaportalEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderMarkedAsPaidEvent::class => [
            MarkOrderAsPushableListener::class,
        ],
    ];
}
