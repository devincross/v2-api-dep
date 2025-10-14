<?php

namespace App\Domains\Tenant\Zoho;

use App\ConnectorResponseObjects\AccountResponseObject;
use App\ConnectorResponseObjects\OrderResponseObject;
use App\Models\Account;
use App\Models\Order;
use App\Services\Tenant\Orders\OrdersService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ZohoUtahDomain extends ZohoDomain
{
    protected function getOrderData($order) {
        $eOrder = $order->getData();
        $account = [
            'account_id'=> $eOrder[$this->config['account_field']],
            'name' => $eOrder['Billing_Name'],
            'dep_account_id'=> $eOrder[$this->config['account_field']]
        ];
        $data = [
            'order_id' => $order->getEntityId(),
            'account' => new AccountResponseObject($account),
            'po' => $eOrder[$this->config['po_field']],
            'is_dep' => true,
            'products' => $this->getSerials($eOrder),
            'status' => $eOrder[$this->config['status']],
            'dep_order_id' => $order->getEntityId(),
            'dep_ordered_at' => $order->getCreatedTime(), //$eOrder[$this->config['dep_ordered_at']],
            'dep_shipped_at' => $order->getModifiedTime(),//$eOrder[$this->config['dep_shipped_at']],
            'source' => Order::SOURCE_ZOHO
        ];
        //todo: add lookup for custom value;
        return new OrderResponseObject($data);
    }

    public function syncAccounts() {
        $this->init();
        return [];
    }

    public function getAllOrders(OrdersService $ordersService) {
        $this->init();

        $accounts = Account::all();
        $count = 1;
        $page = 1;
        $resp = [];
        while($count != 0) {
            $orders = $this->zohoOrdersRepository->getRecentOrders("2015-01-01 00:00:00", $page);
            if(count($orders) < 200) {
                $count = 0;
            }
            foreach($orders as $order) {
                if(stripos($order->getFieldValue('Subject'), "DEP") !== false ) {
                    $eOrder = $this->getOrderData($order);
                    if(count($eOrder->products) > 0) {
                        $resp[] = $eOrder;
                        $account = $accounts->where('external_account_id', '=', $eOrder->account->account_id)->first();
                        if($account == null) {
                            $account = Account::create([
                                'external_account_id'=>$eOrder->account->account_id,
                                'name'=> $eOrder->account->name,
                                'dep_account_id'=>$eOrder->account->dep_account_id]
                            );
                            $accounts = Account::all();
                        }
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
                        try {
                            $lOrder = $ordersService->getExternalOrderId($eOrder->order_id);
                            $ordersService->patch($lOrder->id, $data);
                        } catch(ModelNotFoundException $ex) {
                            $ordersService->create($data);
                        }
                    }
                }
            }
            ++$page;
        }
        return Collection::make($resp);
    }

    public function getRecentOrders($date) {
        $this->init();
        $orders = $this->zohoOrdersRepository->getRecentOrders($date);
        $resp = [];
        foreach($orders as $order) {
            if(stripos($order->getFieldValue('Subject'), "DEP") !== false ) {
                $resp[] = $this->getOrderData($order);
            }
        }
        return Collection::make($resp);
    }

    public function updateOrder($external_order_id, $message) {
        $this->init();
        if($message == Order::STATUS_COMPLETE) {
            return $this->zohoOrdersRepository->updateOrder(
                $external_order_id,
                ["Registered", ""],
                [$this->config['dep_status_field'], "DEP_Errors"]
            );
        } else {
            return $this->zohoOrdersRepository->updateOrder(
                $external_order_id,
                ["Not Registered", $message],
                [$this->config['dep_status_field'], "DEP_Errors"]
            );
        }
    }
}
