<?php

namespace Qubiqx\QcommerceEcommerceMontaportal;

use Qubiqx\QcommerceEcommerceCore\Events\Orders\OrderMarkedAsPaidEvent;
use Qubiqx\QcommerceEcommerceMontaportal\Listeners\MarkOrderAsPushableListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class QcommerceEcommerceMontaportalEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderMarkedAsPaidEvent::class => [
            MarkOrderAsPushableListener::class,
        ],
    ];
}
