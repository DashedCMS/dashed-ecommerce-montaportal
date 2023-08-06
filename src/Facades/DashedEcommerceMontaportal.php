<?php

namespace Dashed\DashedEcommerceMontaportal\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dashed\DashedEcommerceMontaportal\DashedEcommerceMontaportal
 */
class DashedEcommerceMontaportal extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dashed-ecommerce-montaportal';
    }
}
