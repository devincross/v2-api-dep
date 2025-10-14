<?php

namespace App\Repositories\Tenant\Orders;

use App\Models\DepStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\Tenant\Zoho\ZohoService;

class OrdersRepository
{
    public function create($data) {
        $dep_id = $data['dep_order_id'];
        if(stripos($dep_id, "-") !== false) {
            $parts = explode("-", $dep_id);
            $dep_id = $parts[0];
        }
        $count = Order::where('dep_order_id', 'like', "{$dep_id}%")->count();
        if($count > 0) {
            $data['dep_order_id'] = $dep_id."-".$count;
        }
        $order = Order::create($data);
        if(isset($data['products'])) {
            foreach($data['products'] as $serial) {
                Product::create(['serial_number'=>$serial, 'is_dep'=>true, 'order_id'=>$order->id, 'dep_status'=>Product::STATUS_PENDING]);
            }
        }
        return $order;
    }

    public function patch(int $order_id, $data) {
        $order = $this->get($order_id);
        if(isset($data['dep_order_id']) && $data['dep_order_id'] != $order->dep_order_id) {
            $count = Order::where('dep_order_id', '=', $data['dep_order_id'])->count();
            if($count > 0) {
                $data['dep_order_id'] = $data['dep_order_id']."-".$count;
            }
        }
        $order->update($data);
        return $order;
    }

    public function addProducts(int $order_id, $products) {
        foreach($products as $serial) {
            Product::create(['serial_number'=>$serial, 'is_dep'=>true, 'order_id'=>$order_id, 'dep_status'=>Product::STATUS_PENDING]);
        }
        return $this->get($order_id);
    }

    public function removeProducts(int $order_id, $products) {
        foreach($products as $serial) {
            Product::where(['serial_number'=>$serial, 'order_id'=>$order_id])->delete();
        }
        return $this->get($order_id);
    }

    public function cleanProducts(int $order_id) {
        $order = $this->get($order_id);
        $order->products()->update(['dep_status'=>Product::STATUS_COMPLETE]);
        $order->products()->onlyTrashed()->forceDelete();
    }

    public function patchProducts(int $order_id, $serial_number, $status) {
        Product::where(['serial_number'=>$serial_number, 'order_id'=>$order_id])->update(['dep_status'=>$status]);
    }

    public function getProductWithSerial($serial) {
        return Product::where('serial_number', '=', $serial)->firstOrFail();
    }

    public function get(int $order_id) {
        if(stripos($order_id, "-") !== false) {
            return Order::where('order_id', '=', $order_id)->firstOrFail();
        }
        return Order::where('id', '=', $order_id)->firstOrFail();
    }

    public function getExternalOrderId($external_id) {
        return Order::where('external_order_id', '=', $external_id)->firstOrFail();
    }

    public function getOrderLogs($order_id) {
        $order = $this->get($order_id);
        return $order->transactions;
    }

    public function getPendingDepStatusRequests() {
        return DepStatus::where('status', '=', DepStatus::STATUS_PENDING)->get();
    }

    public function getReturnCount($order_id) {
        return Transaction::where('call', '=', 'BulkEnrollDevices:RE')->join('order_transaction', 'transactions.id', '=', 'order_transaction.transaction_id')->where('order_transaction.order_id', '=', $order_id)->count();
    }

    public function getOrders($page, $count) {
        if($page == 0) {
            $page = 1;
        }
        return Order::orderBy('dep_ordered_at', "DESC")->skip(($page-1)*$count)->take($count)->get();
    }

    public function getOrderCount() {
        return Order::count();
    }
}
