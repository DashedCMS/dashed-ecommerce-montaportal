<?php

namespace Dashed\DashedEcommerceMontaportal\Commands;

use Illuminate\Console\Command;
use Dashed\DashedEcommerceCore\Models\Product;
use Dashed\DashedEcommerceMontaportal\Classes\Montaportal;

class SyncProductStockWithMontaportal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'montaportal:sync-product-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync product stock with montaportal';

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
     * @return mixed
     */
    public function handle()
    {
        //        if (env('APP_ENV') != 'local') {
        $products = Product::thisSite()
            ->where('sku', '!=', null)
            ->where('price', '!=', null)
            ->notParentProduct()
            ->isNotBundle()
            ->where('fulfillment_provider', 'montaportal')
            ->get();

        foreach ($products as $product) {
            Montaportal::syncProductStock($product);
        }
        //        }
    }
}
