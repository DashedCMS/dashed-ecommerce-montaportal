<?php

namespace Dashed\DashedEcommerceMontaportal\Commands;

use Illuminate\Console\Command;
use Dashed\DashedEcommerceMontaportal\Classes\Montaportal;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalOrder;

class PushOrdersToMontaportalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'montaportal:push-orders {--debug}';

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
        dd($this->argument('debug'));
        //        if (env('APP_ENV') != 'local') {
        //        MontaportalOrder::where('error', 'LIKE', '%An order with that Webshop Order ID already exists%')->delete();
        $montaPortalOrders = MontaportalOrder::where('pushed_to_montaportal', '!=', 1)->with(['order'])->get();
        $this->info('Orders to push: ' . $montaPortalOrders->count());
        foreach ($montaPortalOrders as $montaPortalOrder) {
            $this->info('Pushing order: ' . $montaPortalOrder->order->id);
            Montaportal::createOrder($montaPortalOrder);
        }
        //        }
    }
}
