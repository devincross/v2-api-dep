<?php

namespace App\Repositories\Tenant\Apple;

use App\Models\Transaction;

class AppleOrderRepository extends BaseRepository
{
    public function getOrder(int $order_id) {
        $transaction = $this->init();
        $order = $this->orderService->get($order_id);
        $data = $this->buildHeader();
        $data['orderNumbers'] = $order->dep_order_id;
        $transaction->call = "ShowOrderDetails";
        $transaction->payload = $data;
        $transaction->save();
        $transaction->orders()->attach($order);

        $resp = $this->call("show-order-details", $data);
        $transaction->response = $resp;
        if(isset($resp['respondedOn'])) {
            $transaction->response_at = gmdate("Y-m-d H:i:s", strtotime($resp['respondedOn']));
        } else {
            $transaction->response_at = gmdate("Y-m-d H:i:s");
        }
        $transaction->response_status = Transaction::RESPONSE_STATUS_ERROR;
        if(isset($resp['errorCode'])) {
            $transaction->response_msg = $resp['errorMessage'];
            $transaction->response_code = $resp['errorCode'];
            $transaction->call_transaction_id = $resp['transactionId'];
            $transaction->save();
            return $transaction;
        }
        if(isset($resp['showOrderErrorResponse'])) {
            $transaction->response_msg = $resp['showOrderErrorResponse']['errorMessage'];
            $transaction->response_code = $resp['showOrderErrorResponse']['errorCode'];
            $transaction->save();
            return $transaction;
        }
        if($resp['statusCode'] == "COMPLETE") {
            $transaction->response_status = Transaction::RESPONSE_STATUS_COMPLETE;
            $transaction->save();
            return $transaction;
        }

        throw new \Exception("Unknown response show-order-details: {$order->id}");
    }
}
