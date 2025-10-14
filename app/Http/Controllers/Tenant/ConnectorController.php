<?php

namespace App\Http\Controllers\Tenant;

use App\Exceptions\InputValidationException;
use App\Http\Controllers\Controller;
use App\Services\Tenant\Orders\OrdersService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use zcrmsdk\oauth\exception\ZohoOAuthException;
use App\Services\Tenant\Zoho\ZohoService;
use App\Services\Tenant\Zoho\ZohoUtahService;

class ConnectorController extends Controller
{
    /** @var OrdersService $ordersService */
    protected $ordersService;

    public function __construct(OrdersService $ordersService) {
        $this->ordersService = $ordersService;
    }

    protected function getConnector() {
        $integration = tenant('integration');
        $className = "App\\Services\\Tenant\\{$integration}";
        return App::make($className);
    }

    public function getRecentOrders(Request $request)
    {
        try {
            $service = $this->getConnector();
            $resp = $service->getOrders(date("Y-m-d\TH:i:s\Z", strtotime("-1 Day")));
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function getAllOrders(Request $request)
    {
        try {
            $service = $this->getConnector();
            $resp = $service->getAllOrders($this->ordersService);
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function syncOrders(Request $request)
    {
        try {
            $resp = $this->ordersService->syncOrders();
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function syncOrderWithSource(Request $request, $order_id) {
        try {
            $resp = $this->ordersService->syncOrderWithSource($order_id);
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function syncOrderWithSourceExternalId(Request $request, $external_order_id) {
        try {
            $resp = $this->ordersService->syncOrderWithSourceExternalId($external_order_id);
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function getOrder(Request $request, $order_id) {
        try {
            $service = $this->getConnector();
            $resp = $service->getOrder($order_id);
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function syncAccounts(Request $request)
    {
        try {
            $service = $this->getConnector();
            $resp = $service->syncAccounts();
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function batchOrders(Request $request) {
        try {
            $service = $this->getConnector();
            $resp = $service->batchAllOrders();
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function batchStatusOrders(Request $request, $id) {
        try {
            $service = $this->getConnector();
            $resp = $service->checkBatchStatus($id);
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function downloadBatchFile(Request $request, $id) {
        try {
            $service = $this->getConnector();
            $resp = $service->downloadBatchFile($id);
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }
}
