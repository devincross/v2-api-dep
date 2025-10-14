<?php

namespace App\Repositories\Tenant\Apple;

class AppleVoidRepository extends BaseRepository
{
    public function process(int $order_id) {
        $transaction = $this->init();
        $order = $this->orderService->get($order_id);

        if(count($order->products) > 0 ) {
            $transaction->call = "BulkEnrollDevices:VD";
            $transaction->save();

            $data = $this->buildHeader($transaction->id);
            $order_data = $this->buildOrderData($order, "VD");
            foreach($order_data['orders'] as $k=>$row) {
                unset($order_data['orders'][$k]['deliveries']);
            }

            $data = array_merge($data, $order_data);

            $transaction->payload = $data;
            $transaction->save();
            $transaction->orders()->attach($order);

            $delete = [];
            foreach($order->products as $product) {
                $delete[] = $product->serial_number;
            }

            $this->orderService->removeProducts($order_id, $delete);

            $resp = $this->call("bulk-enroll-devices", $data);

            return $this->handleEnrollReponses($resp, $transaction);
        }
    }
}
