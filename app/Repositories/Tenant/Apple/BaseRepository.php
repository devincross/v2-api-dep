<?php

namespace App\Repositories\Tenant\Apple;

use App\Models\DepError;
use App\Models\DepStatus;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\Tenant\Orders\OrdersService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BaseRepository
{
    /** @var OrderService */
    protected $orderService;
    protected $config = null;

    public function __construct(OrdersService $ordersService) {
        $this->orderService = $ordersService;
    }

    protected function init() {
        if($this->config == null) {
            $this->config = \Config::get("apple");
        }
        $transaction = new Transaction();
        $transaction->transaction_id = Str::uuid();
        return $transaction;
    }

    /**
     * @throws RequestException
     */
    protected function call($url, $data) {
        $url = $this->config['apple_api_url'].$url;

        return Http::withOptions([
            'debug' => false,
            'verify' => storage_path("../app/cacert.pem"),
            'cert' => storage_path("app/".$this->config['ssl_cert']),
            'ssl_key' => storage_path("app/".$this->config['ssl_key']),
        ])
            ->withHeaders(['Accept'=>'application/json'])
            ->post($url, $data)
            ->throw()
            ->json();
    }

    protected function buildHeader($transaction_id = null) {
        $t = [
            "requestContext"=> [
                "shipTo"=>$this->config['sap_ship_to'],
                'timeZone'=>$this->config['time_zone'],
                'langCode'=>$this->config['lang_code']
            ],
            "depResellerId"=>$this->config['dep_reseller_id']
        ];

        if($transaction_id)
            $t['transactionId'] = "TRNS_".$transaction_id;

        return $t;
    }

    protected function buildOrderData($order, $type, $prefix = null, $postfix = null) {
        $data['orders'] = [[
            "orderNumber" => $prefix.$order->dep_order_id.$postfix,
            "orderDate" => gmdate("Y-m-d\TH:i:s\Z",strtotime($order->dep_ordered_at)),
            "orderType" => $type,
            "customerId" => trim($order->account->dep_account_id),
            "poNumber" => (($order->po != "null")?$order->po:""),
            "deliveries" => [[
                "deliveryNumber" => "D".$order->dep_order_id,
                "shipDate" => gmdate("Y-m-d\TH:i:s\Z",strtotime($order->dep_shipped_at)),
                "devices" => $this->getSerialNumbers($order, $type)
            ]]
        ]];

        return $data;
    }

    protected function getSerialNumbers($order, $type) {
        $devices = [];

        $products = $order->products;
        if($type == "RE") {
            $products = Product::onlyTrashed()->where('order_id', '=', $order->id)->get();
        }

        foreach($products as $p) {
            $devices[] = ['deviceId'=>$p->serial_number];
        }
        return $devices;
    }

    protected function handleEnrollReponses($resp, $transaction) {
        $transaction->response = $resp;
        $transaction->response_at = gmdate("Y-m-d H:i:s");
        $transaction->response_status = Transaction::RESPONSE_STATUS_ERROR;

        if(isset($resp['deviceEnrollmentTransactionId'])){
            $transaction->call_transaction_id = $resp['deviceEnrollmentTransactionId'];
            $transaction->response_code = $resp['enrollDevicesResponse']['statusCode'];
            $transaction->response_msg = $resp['enrollDevicesResponse']['statusMessage'];
            $transaction->response_status = Transaction::RESPONSE_STATUS_COMPLETE;
            $transaction->save();
            return $transaction;
        }
        if(isset($resp['errorCode'])) {
            $transaction->call_transaction_id = $resp['transactionId'];
            $transaction->response_code = $resp['errorCode'];
            $transaction->response_msg = $resp['errorMessage'];
            $transaction->save();
            return $transaction;
        }
        if(isset($resp['enrollDeviceErrorResponse'])) {
            $transaction->response_code = $resp['enrollDeviceErrorResponse']['errorCode'];
            $transaction->response_msg = $resp['enrollDeviceErrorResponse']['errorMessage'];
            $transaction->save();
            return $transaction;
        }
        throw new \Exception("Unknown response show-order-details: {$transaction->orders->first()->order_id}");
    }

    public function getConnector($type = null) {
        return $this->orderService->getConnector($type);
    }

    public function getInternalOrder(int $order_id) {
        return $this->orderService->get($order_id);
    }

    public function setInternalOrderStatus(int $order_id, $message) {
        return $this->orderService->patch($order_id, ['status'=>$message]);
    }

    public function createStatusRequest(int $order_id, $data) {
        $status = DepStatus::create($data);
        $status->orders()->attach($order_id);
        return $status;
    }

    public function getStatusRequest(int $request_id) {
        return DepStatus::where('id', '=', $request_id)->firstOrFail();
    }

    public function logError($data) {
        //need to send this to the parent admin for nate
        return DepError::create($data);
    }
}
