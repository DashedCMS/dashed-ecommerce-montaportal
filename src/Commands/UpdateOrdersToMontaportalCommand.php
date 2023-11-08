<?php

namespace Dashed\DashedEcommerceMontaportal\Commands;

use Illuminate\Console\Command;
use Dashed\DashedEcommerceCore\Models\Order;
use Dashed\DashedEcommerceMontaportal\Classes\Montaportal;

class UpdateOrdersToMontaportalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'montaportal:update-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update orders to Montaportal';

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
        $orders = Order::isPaid()->where('fulfillment_status', '!=', 'handled')->with(['montaPortalOrder'])->get();
        foreach ($orders as $order) {
            Montaportal::updateOrder($order);
        }
        //        }
    }
}
