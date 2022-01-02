<?php

namespace Qubiqx\QcommerceEcommerceMontaportal\Commands;

use Illuminate\Console\Command;
use Qubiqx\QcommerceEcommerceMontaportal\Classes\Montaportal;
use Qubiqx\QcommerceEcommerceMontaportal\Models\MontaportalOrder;

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
        $montaportalOrders = MontaportalOrder::where('pushed_to_montaportal', 1)->where('track_and_trace_present', '!=', 1)->get();
        foreach ($montaportalOrders as $montaportalOrder) {
            Montaportal::updateTrackandTrace($montaportalOrder);
        }
//        }
    }
}
