<?php

namespace App\Console\Commands;

use App\Services\Tenant\Orders\OrdersService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class RunSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:order-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /** @var OrdersService $ordersService */
    protected $ordersService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(OrdersService $ordersService)
    {
        parent::__construct();
        $this->ordersService = $ordersService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $active = tenant('automated');
        if($active) {
            $id = tenant('id');
            $this->info("Syncing: {$id}");
            $resp = $this->ordersService->syncOrders();
            $this->info(json_encode($resp));
        }
    }
}
