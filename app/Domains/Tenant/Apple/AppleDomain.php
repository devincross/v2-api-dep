<?php

namespace App\Domains\Tenant\Apple;

use App\Jobs\DepStatusRequest;
use App\Models\Credential;
use App\Models\DepStatus;
use App\Models\Order;
use App\Models\Transaction;
use App\Repositories\Tenant\Apple\AppleEnrollRepository;
use App\Repositories\Tenant\Apple\AppleOrderRepository;
use App\Repositories\Tenant\Apple\AppleOverrideRepository;
use App\Repositories\Tenant\Apple\AppleRepository;
use App\Repositories\Tenant\Apple\AppleResponseRepository;
use App\Repositories\Tenant\Apple\AppleReturnRepository;
use App\Repositories\Tenant\Apple\AppleVoidRepository;
use App\Services\Tenant\Credentials\CredentialsService;
use Illuminate\Support\Facades\Log;

class AppleDomain
{
    /** @var AppleOrderRepository $appleOrderRepository */
    protected $appleOrderRepository;
    /** @var AppleEnrollRepository $appleEnrollRepository */
    protected $appleEnrollRepository;
    /** @var AppleReturnRepository $appleReturnRepository */
    protected $appleReturnRepository;
    /** @var AppleOverrideRepository $appleOverrideRepository */
    protected $appleOverrideRepository;
    /** @var AppleVoidRepository $appleVoidRepository */
    protected $appleVoidRepository;
    /** @var CredentialsService $credentialsService */
    protected $credentialsService;
    /** @var AppleResponseRepository $appleResponseRepository */
    protected $appleResponseRepository;

    public function __construct(
        AppleOrderRepository $appleRepository,
        AppleEnrollRepository $appleEnrollRepository,
        AppleReturnRepository $appleReturnRepository,
        AppleOverrideRepository $appleOverrideRepository,
        AppleVoidRepository $appleVoidRepository,
        AppleResponseRepository $appleResponseRepository,
        CredentialsService $credentialsService
    ) {
        $this->appleOrderRepository = $appleRepository;
        $this->appleEnrollRepository = $appleEnrollRepository;
        $this->appleOverrideRepository = $appleOverrideRepository;
        $this->appleVoidRepository = $appleVoidRepository;
        $this->appleReturnRepository = $appleReturnRepository;
        $this->appleResponseRepository = $appleResponseRepository;
        $this->credentialsService = $credentialsService;
    }

    protected function init() {
        $credentials = $this->credentialsService->getActiveCredentialByType(Credential::TYPE_DEP);

        if($credentials == null) {
            throw new \Exception("Apple DEP account missing");
        }
        $apple = \Config::get("apple");
        foreach($credentials->connection_data as $key=>$value) {
            $apple[$key] = $value;

        }
        \Config::set("apple", $apple);
    }

    public function getOrder(int $order_id) {
        $this->init();
        return $this->appleOrderRepository->getOrder($order_id);
    }

    public function processOrder(int $order_id, $type) {
        Log::info("Processing dep order: {$order_id} - {$type}");
        $this->init();
        $processor = $this->getProcessClass($type);
        $resp = $processor->process($order_id);
        Log::info("Order send to DEP:");
        $order = $this->appleOrderRepository->getInternalOrder($order_id);
        $connector = $this->appleOrderRepository->getConnector($order->source);
        //check for errors
        if($resp->response_status == Transaction::RESPONSE_STATUS_ERROR) {
            //need to get the errors and update products
            //set source status as error
            $this->appleOrderRepository->setInternalOrderStatus($order->id, Order::STATUS_ERROR);
            $connector->updateOrderStatus($order->external_order_id, $resp->response_msg);
        } else {
            //create dep status queue check
            $data = [
                'transaction_id'=>$resp->call_transaction_id,
                'status'=> DepStatus::STATUS_PENDING,
                'last_run'=> gmdate("Y-m-d\TH:i:s\Z")
            ];
            $request = $this->appleOrderRepository->createStatusRequest($order_id, $data);
            DepStatusRequest::dispatch($request->id, $order->id)->onQueue(tenant("id"))->delay(now()->addMinutes(2));
            $this->appleOrderRepository->setInternalOrderStatus($order->id, Order::STATUS_SUBMITTED);
            $connector->updateOrderStatus($order->external_order_id, Order::STATUS_SUBMITTED);
        }
        return $resp;
    }

    public function processResponse(int $request_id, int $order_id) {
        $this->init();
        $this->appleResponseRepository->process($request_id, $order_id);
    }

    protected function getProcessClass($type) {
        switch($type) {
            case "OR":
                return $this->appleEnrollRepository;
                break;
            case "RE":
                return $this->appleReturnRepository;
                break;
            case "VD":
                return $this->appleVoidRepository;
                break;
            case "OV":
                return $this->appleOverrideRepository;
                break;
        }
        throw new \Exception("Invalid apple order type: {$type}");
    }

    public function compareOrder($order, $aOrder) {
        $this->init();
        if($aOrder->response_status == Transaction::RESPONSE_STATUS_ERROR) {
            return ['error'=>$aOrder->response_msg, 'code'=>$aOrder->response_code];
        }
        $data = json_decode($aOrder->response);
        $eOrder = $data->orders[0];
        $diff = [];
        //check dep_account_id
        if(isset($eOrder->showOrderStatusCode)) {
            return ['error'=>$eOrder->showOrderStatusMessage, 'code'=>$eOrder->showOrderStatusCode];
        }

        if($order->account->dep_account_id != $eOrder->customerId) {
            $diff['account_id'] = $order->account->id;
        }
        //check products
        $serials = [];
        foreach($eOrder->deliveries as $delivery) {
            foreach($delivery->devices as $device) {
                $serials[] = $device->deviceId;
            }
        }
        if(count($serials) != count($order->products)) {
            //new
            foreach($serials as $serial) {
                $found = false;
                foreach($order->products as $product) {
                    if($serial == $product->serial_number) {
                        $found = true;
                        break;
                    }
                }
                if(!$found) {
                    $diff['serials']['new'][] = $serial;
                }
            }
            //remove
            foreach($order->products as $product) {
                $found = false;
                foreach($serials as $serial) {
                    if($serial == $product->serial_number) {
                        $found = true;
                        break;
                    }
                }
                if(!$found) {
                    $diff['serials']['remove'][] = $product->serial_number;
                }
            }
        }
        return $diff;
    }
}
