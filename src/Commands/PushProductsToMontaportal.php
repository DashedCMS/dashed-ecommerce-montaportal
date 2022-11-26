<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Commands;

use Illuminate\Console\Command;
use Qubiqx\QcommerceEcommerceCore\Models\Product;
use Qubiqx\QcommerceEcommerceMontaportal\Classes\Montaportal;

class PushProductsToMontaportal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'montaportal:push-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push products to montaportal';

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
            ->get();

        foreach ($products as $product) {
            $success = Montaportal::createProduct($product);
            if ($success) {
                $this->info('Montaportal product created: ' . $product->id);
            } else {
                $this->error('Montaportal product creation failed: ' . $product->id . ' - ' . $product->name);
            }

            $success = Montaportal::updateProduct($product);
            if ($success) {
                $this->info('Montaportal product updated: ' . $product->id);
            } else {
                $this->error('Montaportal product updating failed: ' . $product->id . ' - ' . $product->name);
            }
        }
//        }
    }
}
