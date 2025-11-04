<?php

namespace App\Repositories\Tenant\Netsuite\Mappings;

use App\ConnectorResponseObjects\AccountResponseObject;
use App\ConnectorResponseObjects\OrderResponseObject;
use App\Models\Product;
use Carbon\Carbon;

class ByuMapping extends BaseMapping
{
    public function getOrder(array $data) {
        if(!isset($data['transaction_type'])) {
            return null;
        }
        $products = [];
        $is_dep = false;
        foreach($data['products'] as $product) {
            $products[] = [
                'serial_number' => $product['serial'],
                'dep_status' => Product::STATUS_PENDING,
                'is_dep' => $product['is_dep']
            ];
            if($product['is_dep']) {
                $is_dep = true;
            }
        }

        $payload = [
            'order_id' => $data['order_id'],
            'account' => $data['account_id'],
            'po' => $data['po'],
            'is_dep' => $is_dep,
            'products' => $products,
            'status' => 'ready',
            'dep_order_id' => $data['order_id'],
            'dep_ordered_at' => Carbon::make($data['date_created']),
            'dep_shipped_at' => Carbon::make($data['last_modified']),
            'source' => 'netsuite'
        ];
        return new OrderResponseObject($payload);
    }

    public function getAccount(array $data) {
        $payload = [
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'dep_account_id' => $data['dep_id']
        ];
        return new AccountResponseObject($payload);
    }
}
