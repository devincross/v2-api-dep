<?php

namespace App\Repositories\Tenant\Apple;

class AppleReturnRepository extends BaseRepository
{
    public function process(int $order_id) {
        $transaction = $this->init();
        $order = $this->orderService->get($order_id);

        $transaction->call = "BulkEnrollDevices:RE";
        $transaction->save();

        $data = $this->buildHeader($transaction->id);
        //get count of rets for this order
        $postfix = "";
        $rCount = $this->orderService->getReturnCount($order_id);
        if($rCount > 0) {
            $postfix = "-{$rCount}";
        }

        $order_data = $this->buildOrderData($order, "RE", "RET:", $postfix);
        $data = array_merge($data, $order_data);

        $transaction->payload = $data;
        $transaction->save();
        $transaction->orders()->attach($order);

        $resp = $this->call("bulk-enroll-devices", $data);

        return $this->handleEnrollReponses($resp, $transaction);
    }
}
