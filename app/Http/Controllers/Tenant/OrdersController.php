<?php

namespace App\Http\Controllers\Tenant;

use App\Exceptions\InputValidationException;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Order;
use App\Models\Product;
use App\Services\Tenant\Apple\AppleService;
use App\Services\Tenant\Orders\OrdersService;
use App\Services\Tenant\Zoho\ZohoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class OrdersController extends Controller
{
    /** @var OrdersService $ordersService */
    protected $ordersService;
    /** @var ZohoService $zohoService */
    protected $zohoService;
    /** @var AppleService $appleService */
    protected $appleService;

    public function __construct(OrdersService $ordersService, ZohoService $zohoService, AppleService $appleService) {
        $this->ordersService = $ordersService;
        $this->zohoService = $zohoService;
        $this->appleService = $appleService;
        $this->middleware('auth:api');
    }

    public function getOrderLogs(Request $request, $order_id) {
        try {
            $resp = $this->ordersService->getOrderLogs($order_id);
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function listOrders(Request $request, $page, $count=10) {
        $resp['orders'] = $this->ordersService->getOrders($page, $count);
        $resp['total_orders'] = $this->ordersService->getOrderCount();
        return response()->json(['status'=>'success', 'results'=>$resp]);
    }

    public function depOrder(Request $request, $order_id) {
        try {
            $resp = $this->appleService->getOrder($order_id);
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function manualEnroll(Request $request, $order_id) {
        try {
            $resp = $this->ordersService->manualEnroll($order_id);
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }
    public function manualOverride(Request $request, $order_id) {
        try {
            $resp = $this->ordersService->manualOverride($order_id);
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }
    public function manualVoid(Request $request, $order_id) {
        try {
            $resp = $this->ordersService->manualVoid($order_id);
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function manualReturn(Request $request, $order_id) {
        try {
            $resp = $this->ordersService->manualReturn($order_id);
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function rescheduleDepStatus(Request $request) {
        try {
            $resp = $this->ordersService->rescheduleDepStatus();
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function importOrder(Request $request) {
        //load account
        $account = Account::where('external_account_id', '=', $request->input('external_account_id'))->first();
        $order_data = $request->all();
        $order_data['account_id'] = $account->id;

        //save order into the system
        $order = "";
        try {
            $order = $this->ordersService->getExternalOrderId($request->input('external_order_id'));
            $order = $this->ordersService->patch($order->id, $order_data);
        } catch (ModelNotFoundException $ex) {
            $order = $this->ordersService->create($order_data);
        }

        //sync products
        Product::whereNotIn('serial_number',$order_data['products'])->delete();
        foreach($order_data['products'] as $serial) {
            Product::firstOrCreate(['serial_number'=>$serial], ['order_id'=>$order->id, 'serial_number'=>$serial, 'is_dep'=>1, 'dep_status'=>Product::STATUS_COMPLETE]);
        }

        if($request->input('source') == Order::SOURCE_ZOHO) {
            //pull order from zoho
            $zOrder = $this->zohoService->getOrder($order->external_order_id);
            //check to see if they have the same account_id, same products/serials
            $diff = $this->zohoService->compareOrder($order, $zOrder);
            if($diff != null) {
                //need to update things
                if(isset($diff['account_id'])) {
                    $order = $this->ordersService->patch($order->id, ['external_account_id'=>$diff['account_id'], 'account_id'=>$account->id]);
                }
                if(isset($diff['serials'])){
                    if(isset($diff['serials']['new'])) {
                        $order = $this->ordersService->addProducts($order->id, $diff['serials']['new']);
                    }
                    if(isset($diff['serials']['remove'])) {
                        $order = $this->ordersService->removeProducts($order->id, $diff['serials']['remove']);
                    }
                }
            }
        }
        $connector = $this->ordersService->getConnector($request->input('source'));
        //pull order from apple
        $dOrder = $this->appleService->getOrder($order->id);
        $aDiff = $this->appleService->compareOrder($order, $dOrder);
        if($aDiff != null) {
            //push up differences - trigger override or enroll
            if(isset($aDiff['error'])) {
                //enroll
                $this->appleService->processOrder($order->id, 'OR');
            } else {
                //override
                $this->appleService->processOrder($order->id, 'OV');
            }

            if($request->input('source') == Order::SOURCE_ZOHO) {
                //update status in zoho
                $connector->updateOrderStatus($order->external_order_id, Order::STATUS_SUBMITTED);
            }
        } else {

            if($request->input('source') == Order::SOURCE_ZOHO) {
                //update status in zoho
                $connector->updateOrderStatus($order->external_order_id, Order::STATUS_COMPLETE);
            }
        }

        return response()->json(['status'=>'success', 'results'=>$order]);
    }

    public function depStatusAudit() {
        $resp = [];
        Order::where("status", "!=", 'complete')->chunk(50, function ($orders) use($resp){
            foreach($orders as $order ) {
                $connector = $this->ordersService->getConnector($order->source);
                //pull order from apple
                $dOrder = $this->appleService->getOrder($order->id);
                $aDiff = $this->appleService->compareOrder($order, $dOrder);
                if($aDiff != null) {
                    //push up differences - trigger override or enroll
                    if(isset($aDiff['error'])) {
                        //enroll
                        //$this->appleService->processOrder($order->id, 'OR');
                    } else {
                        //override
                        //$this->appleService->processOrder($order->id, 'OV');
                    }

                    //update status in zoho
                    //$connector->updateOrderStatus($order->external_order_id, Order::STATUS_SUBMITTED);
                } else {
                    //update status in zoho
                    $resp[] = $order->id;
                    $connector->updateOrderStatus($order->external_order_id, Order::STATUS_COMPLETE);
                    $this->ordersService->patch($order->id, ['status'=>Order::STATUS_COMPLETE]);
                }
            }
        });
        return response()->json(['status'=>'success', 'results'=>$resp]);
    }
}
