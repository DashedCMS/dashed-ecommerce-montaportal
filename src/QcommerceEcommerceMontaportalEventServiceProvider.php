<?php

namespace Qubiqx\QcommerceEcommerceMontaportal;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Qubiqx\QcommerceEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;
use Qubiqx\QcommerceEcommerceMontaportal\Listeners\MarkOrderAsPushableListener;

class QcommerceEcommerceMontaportalEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderMarkedAsPaidEvent::class => [
            MarkOrderAsPushableListener::class,
        ],
    ];
}
