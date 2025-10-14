<?php

namespace App\Domains\Tenant\Zoho;

use App\ConnectorResponseObjects\AccountResponseObject;
use App\ConnectorResponseObjects\OrderResponseObject;
use App\Exceptions\InputValidationException;
use App\Models\Account;
use App\Models\Credential;
use App\Models\Order;
use App\Repositories\Tenant\Zoho\ZohoAccountsRepository;
use App\Repositories\Tenant\Zoho\ZohoOrdersRepository;
use App\Repositories\Tenant\Zoho\ZohoRepository;
use App\Services\Tenant\Credentials\CredentialsService;
use App\Services\Tenant\Orders\OrdersService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;
use Asciisd\Zoho\Zoho;

class ZohoDomain
{
    /** @var ZohoRepository $zohoRepository */
    protected $zohoRepository;
    /** @var ZohoOrdersRepository $zohoOrdersRepository */
    protected $zohoOrdersRepository;
    /** @var ZohoAccountsRepository $zohoAccountsRepository */
    protected $zohoAccountsRepository;
    /** @var CredentialsService $credentialsService */
    protected $credentialsService;
    protected $config;

    public function __construct(
        CredentialsService $credentialsService,
        ZohoRepository $zohoRepository,
        ZohoOrdersRepository $zohoOrdersRepository,
        ZohoAccountsRepository $zohoAccountsRepository
    ) {
        $this->credentialsService = $credentialsService;
        $this->zohoRepository = $zohoRepository;
        $this->zohoOrdersRepository = $zohoOrdersRepository;
        $this->zohoAccountsRepository = $zohoAccountsRepository;
    }

    protected function init() {
        $credentials = $this->credentialsService->getActiveCredentialByType(Credential::TYPE_ZOHO);

        if($credentials == null) {
            throw new \Exception("Zoho account missing");
        }
        $zoho = \Config::get("zoho");
        foreach($credentials->connection_data as $key=>$value) {
            if($key == "application_log_file_path" || $key == "token_persistence_path") {
                $value = storage_path($value);
            }
            $zoho[$key] = $value;

        }
        \Config::set("zoho", $zoho);
        $this->config = $zoho;
    }

    public function activateConnection() {
        $this->init();
        $exitCode = \Artisan::call('tenants:run zoho:connect --tenants='.tenant('id'));
        return \Artisan::output();
    }

    public function oAuthSetup($request) {
        $this->init();
        ZCRMRestClient::initialize(Zoho::zohoOptions());
        $oAuthClient = ZohoOAuth::getClientInstance();
        $oAuthClient->generateAccessToken($request['code']);

        return 'Zoho CRM has been set up successfully.';
    }

    public function getRecentOrders($date) {
        $this->init();
        $orders = $this->zohoOrdersRepository->getRecentOrders($date);
        $resp = [];
        foreach($orders as $order) {
            if($order->getFieldValue($this->config['is_dep_field'])) {
                $eo = $this->getOrderData($order);
                if($eo != null) {
                    $resp[] = $eo;
                }
            }
        }
        return Collection::make($resp);
    }

    public function getAllOrders(OrdersService $ordersService) {
        $this->init();
        $this->syncAccounts();

        $accounts = Account::all();
        $count = 1;
        $page = 1;
        $resp = [];
        while($count != 0) {
            $orders = $this->zohoOrdersRepository->getRecentOrders("2015-01-01 00:00:00", $page);
            if(count($orders) < 200) {
                $count = 0;
            }
            foreach($orders as $order) {
                if($order->getFieldValue($this->config['is_dep_field'])) {
                    $eOrder = $this->getOrderData($order);
                    if($eOrder != null && count($eOrder->products) > 0) {
                        $resp[] = $eOrder;
                        $account = $accounts->where('external_account_id', '=', $eOrder->account->account_id)->first();
                        $data = [
                            'external_order_id'=> $eOrder->order_id,
                            'account_id' => $account->id,
                            'external_account_id' => $eOrder->account_id,
                            'external_order_status' => $eOrder->status,
                            'status' => Order::STATUS_PENDING,
                            'po' => $eOrder->po,
                            'dep_order_id'=> $eOrder->dep_order_id,
                            'dep_ordered_at' => date('Y-m-d H:i:s', strtotime($eOrder->dep_ordered_at)),
                            'dep_shipped_at' => date('Y-m-d H:i:s', strtotime($eOrder->dep_shipped_at)),
                            'source' => $eOrder->source,
                            'products' => $eOrder->products
                        ];
                        try {
                            $lOrder = $ordersService->getExternalOrderId($eOrder->order_id);
                            $ordersService->patch($lOrder->id, $data);
                        } catch(ModelNotFoundException $ex) {
                            $ordersService->create($data);
                        }
                    }
                }
            }
            ++$page;
        }
        return Collection::make($resp);
    }

    public function getOrder($external_order_id) {
        $this->init();
        $order = $this->zohoOrdersRepository->getOrder($external_order_id);
        return $this->getOrderData($order);
    }

    public function updateOrder($external_order_id, $message) {
        $this->init();
        return $this->zohoOrdersRepository->updateOrder($external_order_id, $message, $this->config['dep_status_field']);
    }

    public function compareOrder($order, $zOrder) {
        $diff = [];
        //get zAccountId
        $external_account_id = $zOrder->account->external_id;
        if($order->external_account_id != $external_account_id) {
            //$diff['account_id'] = $external_account_id;
        }

        $zSerials = $zOrder->products;
        //new
        foreach($zSerials as $serial) {
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
            foreach($zSerials as $serial) {
                if($serial == $product->serial_number) {
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                $diff['serials']['remove'][] = $product->serial_number;
            }
        }
        return $diff;
    }

    protected function getOrderData($order) {
        $eOrder = $order->getData();
        $eAccount = $order->getFieldValue('Account_Name');
        if($eAccount == null) {
            $this->updateOrder($order->getEntityId(),"Error: no account on this order");
            Log::info("Error: {$order->getEntityId()} is missing the account");
            return null;
        }
        $account = [
            'account_id'=>$eAccount->getEntityId(),
            'name' => $eAccount->getLookUpLabel(),
            'dep_account_id'=> $this->getDepId($eAccount->getEntityId())
        ];
        $dep_order_id = 0;
        if($this->config['dep_order_id'] == "entity_id") {
            $dep_order_id = $order->getEntityId();
        } else {
            $dep_order_id = $eOrder[$this->config['dep_order_id']];
        }

        $data = [
            'order_id' => $order->getEntityId(),
            'account' => new AccountResponseObject($account),
            'po' => $eOrder[$this->config['po_field']],
            'is_dep' => $eOrder[$this->config['is_dep_field']],
            'products' => $this->getSerials($eOrder),
            'status' => $eOrder[$this->config['status']],
            'dep_order_id' => $dep_order_id,
            'dep_ordered_at' => $order->getCreatedTime(), //$eOrder[$this->config['dep_ordered_at']],
            'dep_shipped_at' => $order->getModifiedTime(),//$eOrder[$this->config['dep_shipped_at']],
            'source' => Order::SOURCE_ZOHO
        ];
        //todo: add lookup for custom value;
        return new OrderResponseObject($data);
    }

    protected function getSerials($order) {
        $serial_string = $order[$this->config['serials_field']];
        $res = [];
        if(stripos($serial_string, ",") !== false) {
            $res = explode(",", $serial_string);
        } else if(stripos($serial_string, "\n") !== false) {
            $res = explode("\n", $serial_string);
        } else {
            $res[] = $serial_string;
        }

        $cleanSerials = [];
        foreach($res as $serial) {
            $cleanSerial = trim(trim($serial));
            if($cleanSerial == "")
                continue;
            if(strtolower($cleanSerial[0]) == "s")
                $cleanSerial = substr($cleanSerial, 1);
            $cleanSerials[] = $cleanSerial;
        }

        return $cleanSerials;
    }

    public function syncAccounts() {
        $this->init();
        $accounts = $this->zohoAccountsRepository->getAllAcounts();
        $resp = [];
        foreach($accounts as $account) {
            if($account->getFieldValue($this->config['account_field']) == "") {
                continue;
            }
            $resp[] = $account->getFieldValue('Account_Name');
            $data = [
                'name'=>$account->getFieldValue('Account_Name'),
                'external_account_id'=>$account->getEntityId(),
                'dep_account_id'=>$account->getFieldValue($this->config['account_field'])
            ];
            Account::updateOrCreate(['external_account_id'=>$data['external_account_id']], $data);
        }
        return $resp;
    }

    protected function getDepId($external_account_id) {
        $id = Account::where('external_account_id', '=', $external_account_id)->first();
        if($id != null) {
            return $id->dep_account_id;
        }
        return "";
    }

    public function getAllAccounts() {
        $this->init();
        $accounts = $this->zohoAccountsRepository->getAllAcounts();
        $resp = [];
        foreach($accounts as $account) {
            $resp[] =  [
                'name'=>$account->getFieldValue('Account_Name'),
                'external_account_id'=>$account->getEntityId(),
                'dep_account_id'=>$account->getFieldValue($this->config['account_field'])
            ];
        }
        return $resp;
    }

    public function batchAllOrders() {
        return $this->zohoOrdersRepository->batchAllOrders();
    }

    public function checkBatchStatus($id) {
        return $this->zohoOrdersRepository->checkBatchStatus($id);
    }

    public function downloadBatchFile($id) {
        return $this->zohoOrdersRepository->downloadBatchFile($id);
    }
}
