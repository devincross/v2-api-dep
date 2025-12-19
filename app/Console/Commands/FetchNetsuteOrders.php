<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class FetchNetsuteOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-netsute-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $integration = tenant('integration');
        $className = "App\\Services\\Tenant\\{$integration}";
        $connector = App::make($className);

        $id = tenant('id');
        $this->info("Syncing: {$id}");
        $resp = $connector->getOrders(date("Y-m-d\TH:i:s\Z", strtotime("-40 Days")));
        $this->info(json_encode($resp));

    }
}
