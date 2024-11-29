<?php

namespace Dashed\DashedEcommerceMontaportal\Commands;

use Illuminate\Console\Command;

class SyncUnconnectedMontaportalOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-unconnected-montaportal-orders {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync unconnected Montaportal orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $count = 0;

        foreach (\Dashed\DashedEcommerceCore\Models\Order::where('created_at', '>=', now()->subMinutes(5))->isPaid()->get() as $order) {
            if (! $order->montaPortalOrder) {
                $count++;
                $this->info('Creating Montaportal order for order: ' . $order->id);
                if (! $isDryRun) {
                    $order->montaPortalOrder()->create([]);
                }
            }
        }

        if ($isDryRun) {
            $this->info('Dry run, no orders created, count is ' . $count);
        } else {
            $this->info('Created ' . $count . ' Montaportal orders');
        }
    }
}
