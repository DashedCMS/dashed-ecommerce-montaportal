<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Commands;

use Illuminate\Console\Command;

class QcommerceEcommerceMontaportalCommand extends Command
{
    public $signature = 'qcommerce-ecommerce-montaportal';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
