<?php

namespace App\Repositories\Tenant\Apple;

class AppleOverrideRepository extends BaseRepository
{
    public function process(int $order_id) {
        $transaction = $this->init();
        $order = $this->orderService->get($order_id);

        if(count($order->products) > 0 ) {
            $transaction->call = "BulkEnrollDevices:OV";
            $transaction->save();

            $data = $this->buildHeader($transaction->id);
            $order_data = $this->buildOrderData($order, "OV");
            $data = array_merge($data, $order_data);

            $transaction->payload = $data;
            $transaction->save();
            $transaction->orders()->attach($order);

            $resp = $this->call("bulk-enroll-devices", $data);

            return $this->handleEnrollReponses($resp, $transaction);
        }
    }
}
