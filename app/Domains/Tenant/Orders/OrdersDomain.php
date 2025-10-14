<?php

namespace App\Domains\Tenant\Orders;

use App\Jobs\DepProcessOrder;
use App\Jobs\DepStatusRequest;
use App\Models\Account;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Repositories\Tenant\Orders\OrdersRepository;
use App\Services\Tenant\Apple\AppleService;
use App\Services\Tenant\Zoho\ZohoService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class OrdersDomain
{
    /** @var OrdersRepository $ordersRepository */
    protected $ordersRepository;

    public function __construct(OrdersRepository $ordersRepository) {
        $this->ordersRepository = $ordersRepository;
    }

    public function create($data) {
        return $this->ordersRepository->create($data);
    }

    public function patch(int $order_id, $data) {
        return $this->ordersRepository->patch($order_id, $data);
    }

    public function get(int $order_id) {
        return $this->ordersRepository->get($order_id);
    }

    public function getExternalOrderId($external_id) {
        return $this->ordersRepository->getExternalOrderId($external_id);
    }

    public function syncOrders() {
        $connector = $this->getConnector();
        $connector->syncAccounts();
        /** @var Collection $accounts */
        $accounts = Account::all();
        $orders = $connector->getOrders(date("Y-m-d\TH:i:s\Z", strtotime("-1 Day")));
        $resp = [];
        foreach($orders as $eOrder) {
            $row = ['external_id' => $eOrder->order_id];
            $row['status'] = $this->syncOrder($eOrder, $accounts, $connector);
            $resp[] = $row;
        }
        return $resp;
    }

    public function syncOrderWithSource(int $order_id) {
        $connector = $this->getConnector();
        $connector->syncAccounts();
        /** @var Collection $accounts */
        $accounts = Account::all();
        $lOrder = $this->get($order_id);
        $eOrder = $connector->getOrder($lOrder->external_order_id);
        $resp = [];

        $row = ['external_id' => $eOrder->order_id];
        $row['status'] = $this->syncOrder($eOrder, $accounts, $connector);
        $resp[] = $row;

        return $resp;
    }

    public function syncOrderWithSourceExternalId(string $external_order_id) {
        $connector = $this->getConnector();
        $connector->syncAccounts();
        /** @var Collection $accounts */
        $accounts = Account::all();
        $eOrder = $connector->getOrder($external_order_id);
        $resp = [];

        $row = ['external_id' => $eOrder->order_id];
        $row['status'] = $this->syncOrder($eOrder, $accounts, $connector);
        $resp[] = $row;

        return $resp;
    }

    public function syncOrder($eOrder, $accounts, $connector) {
        //load account
        $account = $accounts->where('external_account_id', '=', $eOrder->account->account_id)->first();
        if($account == null) {
            dd("account not found");
        }

        //findLocal order
        try {
            $lOrder = $this->getExternalOrderId($eOrder->order_id);
            //get diffs
            $diff = $connector->compareOrder($lOrder, $eOrder);
            $call = "";
            if(isset($diff['account_id'])) {
                $this->ordersRepository->patch($lOrder->id, ['external_account_id'=>$diff['account_id'], 'account_id'=>$account->id]);
                $call = "OV";
            }
            if(isset($diff['serials'])){
                if(isset($diff['serials']['new'])) {
                    //validate serials
                    foreach($diff['serials']['new'] as $serial) {
                        $p = Product::where('serial_number', '=', $serial)->first();
                        if($p != null) {
                            //notify source of error
                            $connector->updateOrderStatus($eOrder->order_id, "Serial number already in DEP: {$serial}");
                            $lOrder->status = Order::STATUS_ERROR;
                            $lOrder->save();
                            return Order::STATUS_ERROR;
                        }
                    }
                    $this->ordersRepository->addProducts($lOrder->id, $diff['serials']['new']);
                    $this->ordersRepository->patch($lOrder->id, ['dep_shipped_at'=>Carbon::now()]);
                    $call = "OV";
                }
                if(isset($diff['serials']['remove'])) {
                    $this->ordersRepository->removeProducts($lOrder->id, $diff['serials']['remove']);
                    if($call != "OV") {
                        $call = "RE";
                    }
                }
            }
            if($call != "") {
                DepProcessOrder::dispatch($lOrder->id, $call)->onQueue(tenant("id"));
                return Order::STATUS_SUBMITTED;
            }
        } catch (ModelNotFoundException $ex) {
            //make sure serial numbers not in the system
            foreach($eOrder->products as $serial) {
                $p = Product::where('serial_number', '=', $serial)->first();
                if($p != null) {
                    //notify source of error
                    $connector->updateOrderStatus($eOrder->order_id, "Serial number already in DEP: {$serial}");
                    return Order::STATUS_ERROR;
                }
            }
            //need to create order
            $data = [
                'external_order_id'=> $eOrder->order_id,
                'account_id' => $account->id,
                'external_account_id' => $eOrder->account->account_id,
                'external_order_status' => $eOrder->status,
                'status' => Order::STATUS_PENDING,
                'po' => $eOrder->po,
                'dep_order_id'=> $eOrder->dep_order_id,
                'dep_ordered_at' => date('Y-m-d H:i:s', strtotime($eOrder->dep_ordered_at)),
                'dep_shipped_at' => date('Y-m-d H:i:s', strtotime($eOrder->dep_shipped_at)),
                'source' => $eOrder->source,
                'products' => $eOrder->products
            ];
            $lOrder = $this->create($data);
            //schedule enroll
            DepProcessOrder::dispatch($lOrder->id, "OR")->onQueue(tenant("id"));
            return Order::STATUS_SUBMITTED;
        }
        return "No Changes";
    }

    public function getConnector($type = null){
        if($type == null) {
            $integration = tenant('integration');
            $className = "App\\Services\\Tenant\\{$integration}";
            return App::make($className);
        }
        switch($type) {
            case Tenant::INTEGRATION_ZOHO:
                $integration = tenant('integration');
                $className = "App\\Services\\Tenant\\{$integration}";
                return App::make($className);
                break;
        }
    }

    public function addProducts(int $order_id, $products) {
        return $this->ordersRepository->addProducts($order_id, $products);
    }

    public function removeProducts(int $order_id, $products) {
        return $this->ordersRepository->removeProducts($order_id, $products);
    }

    public function cleanProducts(int $order_id) {
        return $this->ordersRepository->cleanProducts($order_id);
    }

    public function patchProducts(int $order_id, $serial_number, $status) {
        return $this->ordersRepository->patchProducts($order_id, $serial_number, $status);
    }

    public function getProductWithSerial($serial) {
        return $this->ordersRepository->getProductWithSerial($serial);
    }

    public function getOrderLogs($order_id) {
        return $this->ordersRepository->getOrderLogs($order_id);
    }

    public function manualEnroll($order_id) {
        DepProcessOrder::dispatch($order_id, "OR")->onQueue(tenant("id"));
    }
    public function manualOverride($order_id) {
        DepProcessOrder::dispatch($order_id, "OV")->onQueue(tenant("id"));
    }
    public function manualVoid($order_id) {
        DepProcessOrder::dispatch($order_id, "VD")->onQueue(tenant("id"));
    }
    public function manualReturn($order_id) {
        DepProcessOrder::dispatch($order_id, "RE")->onQueue(tenant("id"));
    }
    public function rescheduleDepStatus() {
        $resp = [];
        foreach($this->ordersRepository->getPendingDepStatusRequests() as $request) {
            $order = $request->orders()->first();
            DepStatusRequest::dispatch($request->id, $order->id)->onQueue(tenant("id"));
            $resp[] = "Rescheduled: Order({$order->order_id} Transaction: ({$request->transaction_id})";
        }
        return $resp;
    }

    public function getReturnCount($order_id) {
        return $this->ordersRepository->getReturnCount($order_id);
    }

    public function getOrders($page, $count) {
        return $this->ordersRepository->getOrders($page,$count);
    }

    public function getOrderCount() {
        return $this->ordersRepository->getOrderCount();
    }
}
