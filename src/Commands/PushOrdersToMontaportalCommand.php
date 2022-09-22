<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Commands;

use Illuminate\Console\Command;
use Qubiqx\QcommerceEcommerceMontaportal\Classes\Montaportal;
use Qubiqx\QcommerceEcommerceMontaportal\Models\MontaportalOrder;

class PushOrdersToMontaportalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'montaportal:push-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push orders to Montaportal';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
//        if (env('APP_ENV') != 'local') {
        MontaportalOrder::where('error', 'LIKE', '%An order with that Webshop Order ID already exists%')->delete();
        $montaPortalOrders = MontaportalOrder::where('pushed_to_montaportal', '!=', 1)->with(['order'])->get();
        foreach ($montaPortalOrders as $montaPortalOrder) {
            Montaportal::createOrder($montaPortalOrder);
        }
//        }
    }
}
