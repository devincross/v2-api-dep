<?php

namespace App\Repositories\Tenant\Apple;

use App\Jobs\DepStatusRequest;
use App\Models\DepStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;

class AppleResponseRepository extends BaseRepository
{
    public function process(int $request_id, int $order_id) {
        $transaction = $this->init();
        $request = $this->getStatusRequest($request_id);
        $transaction->call = "checkTransactionStatus";

        $order = $this->getInternalOrder($order_id);
        $connector = $this->getConnector($order->source);

        $data = $this->buildHeader();
        $data['deviceEnrollmentTransactionId'] = $request->transaction_id;

        $transaction->payload = $data;
        $transaction->save();
        $transaction->orders()->attach($order);

        $request->status = DepStatus::STATUS_COMPLETE;

        $resp = $this->call("check-transaction-status", $data);
        $transaction->response = $resp;
        if(isset($resp['completedOn'])) {
            $transaction->response_at = gmdate("Y-m-d H:i:s", strtotime($resp['completedOn']));
        } else {
            $transaction->response_at = gmdate("Y-m-d H:i:s");
        }
        $transaction->response_status = Transaction::RESPONSE_STATUS_ERROR;
        if(isset($resp['errorCode'])) {
            $transaction->response_code = $resp['errorCode'];
            $transaction->response_msg = $resp['errorMessage'];
            $transaction->save();
            $connector->updateOrderStatus($order->external_order_id, $transaction->response_msg);
            $this->logError(['order_id'=>$order_id, 'request_id'=>$request_id, 'error_code'=>$transaction->response_code, 'error'=>$transaction->response_msg ]);
            //don't reschedule - there is an error that needs manual fixing
            return $transaction;
        }
        if(isset($resp['checkTransactionErrorResponse'])) {
            //don't reschedule - there is an error that needs manual fixing
            if(isset($resp['deviceEnrollmentTransactionID'])) {
                $transaction->call_transaction_id = $resp['deviceEnrollmentTransactionID'];
            }
            $transaction->response_code = $resp['checkTransactionErrorResponse'][0]['errorCode'];
            $transaction->response_msg = $resp['checkTransactionErrorResponse'][0]['errorMessage'];
            $transaction->save();
            if($transaction->response_code == "DEP-ERR-4003") {
                //need to reschedule the job
                $request->status = DepStatus::STATUS_PENDING;
                DepStatusRequest::dispatch($request_id, $order->id)->onQueue(tenant("id"))->delay(now()->addMinutes(2));
            } else {
                $this->logError(['order_id'=>$order_id, 'request_id'=>$request_id, 'error_code'=>$transaction->response_code, 'error'=>$transaction->response_msg ]);
                $connector->updateOrderStatus($order->external_order_id, $transaction->response_msg);
            }
            return $transaction;
        } else if(isset($resp['deviceEnrollmentTransactionID'])) {
            $transaction->call_transaction_id = $resp['transactionId'];
            $transaction->response_code = $resp['statusCode'];
            if($resp['statusCode'] == "COMPLETE") {
                $this->orderService->patch($order_id, ['status' => Order::STATUS_COMPLETE]);
                $this->orderService->cleanProducts($order_id);
                $transaction->response_status = Transaction::RESPONSE_STATUS_COMPLETE;
                $transaction->response_msg = "COMPLETE";
            } else {
                foreach ($resp['orders'] as $aOrder) {
                    if ($aOrder['orderPostStatus'] == "COMPLETE") {
                        $this->orderService->patch($order_id, ['status' => Order::STATUS_COMPLETE]);
                        $this->orderService->cleanProducts($order_id);
                        $transaction->response_msg = Order::STATUS_COMPLETE;
                    } else {
                        $this->orderService->patch($order_id, ['status' => Order::STATUS_ERROR]);
                        if(isset($aOrder['deliveries'])) {
                            foreach ($aOrder['deliveries'] as $delivery) {
                                if(isset($delivery['devices'])) {
                                    foreach ($delivery['devices'] as $device) {
                                        //if complete mark as complete
                                        if ($device['devicePostStatus'] == "COMPLETE") {
                                            //mark product as complete
                                            $this->orderService->patchProducts($order_id, $device['deviceId'], Product::STATUS_COMPLETE);
                                        } else {
                                            //mark as error
                                            $message = "{$device['deviceId']}({$device['devicePostStatus']}) - {$device['devicePostStatusMessage']}";
                                            $this->logError([
                                                'order_id' => $order_id,
                                                'request_id' => $request_id,
                                                'product_id' => $this->orderService->getProductWithSerial($device['deviceId'])->id,
                                                'error_code' => $transaction->response_code,
                                                'error' => $message
                                            ]);
                                            $transaction->response_msg .= $message;
                                            $this->orderService->patchProducts($order_id, $device['deviceId'], Product::STATUS_ERROR);
                                        }
                                    }
                                } else {
                                    $transaction->response_code = $delivery['deliveryPostStatus'];
                                    $transaction->response_msg = $delivery['deliveryPostStatusMessage'];
                                    $this->logError(['order_id'=>$order_id, 'request_id'=>$request_id, 'error_code'=>$delivery['deliveryPostStatus'], 'error'=>$delivery['deliveryPostStatusMessage'] ]);
                                }
                            }
                        } else {
                            $transaction->response_code = $aOrder['orderPostStatus'];
                            $transaction->response_msg = $aOrder['orderPostStatusMessage'];
                            $this->logError(['order_id'=>$order_id, 'request_id'=>$request_id, 'error_code'=>$aOrder['orderPostStatus'], 'error'=>$aOrder['orderPostStatusMessage'] ]);
                        }
                    }
                }
            }
            //update orderSource status
            $connector->updateOrderStatus($order->external_order_id, $transaction->response_msg);
            $transaction->save();
            $request->save();
        }

        return $transaction;
    }
}
