<?php

namespace App\Repositories\Tenant\Apple;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class AppleEnrollRepository extends BaseRepository
{
    public function process(int $order_id) {
        $transaction = $this->init();
        $order = $this->orderService->get($order_id);

        if(count($order->products) > 0 ) {
            $transaction->call = "BulkEnrollDevices:OR";
            $transaction->save();

            $data = $this->buildHeader($transaction->id);
            $order_data = $this->buildOrderData($order, "OR");
            $data = array_merge($data, $order_data);

            $transaction->payload = $data;
            $transaction->save();
            $transaction->orders()->attach($order);

            $resp = $this->call("bulk-enroll-devices", $data);
            Log::info($resp);

            return $this->handleEnrollReponses($resp, $transaction);
        }
    }
}
