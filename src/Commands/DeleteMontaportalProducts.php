<?php

namespace Dashed\DashedEcommerceMontaportal\Commands;

use Illuminate\Console\Command;
use Dashed\DashedEcommerceMontaportal\Models\MontaportalProduct;

class DeleteMontaportalProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'montaportal:delete-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete products from montaportal';

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
        foreach (MontaportalProduct::all() as $montaportalProduct) {
            if (! $montaportalProduct->product) {
                $montaportalProduct->delete();
            }
        }
    }
}
