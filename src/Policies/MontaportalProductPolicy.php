<?php

namespace Dashed\DashedEcommerceMontaportal\Policies;

use Dashed\DashedCore\Policies\BaseResourcePolicy;

class MontaportalProductPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'MontaportalProduct';
    }
}
