<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Tenant\Orders\OrdersService;
use App\Services\Tenant\Zoho\ZohoService;
use Illuminate\Console\Command;

class FixDepIdForDuke extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(OrdersService $ordersService, ZohoService $zoho)
    {
        parent::__construct();
        $this->ordersService = $ordersService;
        $this->zohoService = $zoho;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orders = Order::all();
        foreach($orders as $order) {
            $zOrder = $this->zohoService->getOrder($order->external_order_id);
            $resp = $this->ordersService->patch($order->id, ['dep_order_id'=>$zOrder->dep_order_id]);
            dd($resp);
        }
    }
}
