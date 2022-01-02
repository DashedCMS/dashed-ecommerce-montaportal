<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Qubiqx\QcommerceEcommerceMontaportal\QcommerceEcommerceMontaportal
 */
class QcommerceEcommerceMontaportal extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'qcommerce-ecommerce-montaportal';
    }
}
