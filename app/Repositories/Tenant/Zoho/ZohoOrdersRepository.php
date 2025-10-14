<?php

namespace App\Repositories\Tenant\Zoho;
use Asciisd\Zoho\Facades\ZohoManager;
use Asciisd\Zoho\ZohoModule;
use Asciisd\Zoho\CriteriaBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\utility\ZCRMConfigUtil;
use zcrmsdk\oauth\ZohoOAuthClient;

class ZohoOrdersRepository
{
    /** @var  ZohoModule $orders*/
    protected $orders;

    protected function init() {
        $this->orders = ZohoManager::useModule('Sales_Orders');
    }

    public function getRecentOrders($date, $page = 1) {
        $this->init();
        $headers = ['If-Modified-Since'=>$date];
        $params = ['page'=>$page];
        try {
            return $this->orders->getRecords($page, 200, $headers);
        } catch(ZCRMException $ex) {
            //no orders found
            return [];
        }
    }

    public function updateOrder($zoho_id, $message, $field_name) {
        $this->init();
        $order = $this->orders->getRecord($zoho_id);
        if(is_array($field_name)) {
            foreach($field_name as $k=>$value) {
                $message[$k] = substr($message[$k], 0, 120);
                $order->setFieldValue($value, $message[$k]);
            }
        } else {
            $message = substr($message, 0, 120);
            $order->setFieldValue($field_name, $message);
        }
        try {
            return $order->update();
        } catch (\Throwable $ex) {
            //dump($order);
            dump($ex->getMessage().":{$zoho_id}");
        }
    }

    public function getOrder($external_order_id) {
        $this->init();
        return $this->orders->getRecord($external_order_id);
    }

    public function batchAllOrders() {
        $token = storage_path("/app/zoho/oauth/tokens/zcrm_oauthtokens.txt");
        $tokens = unserialize(file_get_contents($token));
        $auth = $tokens[0]->getAccessToken();

        $data = ['query'=>["module"=>['api_name'=>"Sales_Orders"]]];
        return Http::withHeaders(["Authorization"=> "Zoho-oauthtoken {$auth}", "Content-Type"=>"application/json"])
            ->post("https://www.zohoapis.com/crm/bulk/v3/read", $data)
            ->json();
    }

    public function checkBatchStatus($id) {
        $token = storage_path("/app/zoho/oauth/tokens/zcrm_oauthtokens.txt");
        $tokens = unserialize(file_get_contents($token));
        $auth = $tokens[0]->getAccessToken();

        return Http::withHeaders(["Authorization"=> "Zoho-oauthtoken {$auth}", "Content-Type"=>"application/json"])
            ->get("https://www.zohoapis.com/crm/bulk/v3/read/{$id}")
            ->json();
    }

    public function downloadBatchFile($id) {
        $token = storage_path("/app/zoho/oauth/tokens/zcrm_oauthtokens.txt");
        $tokens = unserialize(file_get_contents($token));
        $auth = $tokens[0]->getAccessToken();

        $client = new Client(['headers'=>["Authorization"=> "Zoho-oauthtoken {$auth}"]]);
        $path = storage_path("app/app/zoho/{$id}.zip");

        $resource = fopen($path, 'w');
        $stream = Utils::streamFor($resource);
        $client->request('GET', "https://www.zohoapis.com/crm/bulk/v3/read/{$id}/result", ['save_to' => $stream]);
        //unzip
        exec("unzip {$path}");
    }

    public function importBatchFile($id) {
        //Id,SO_Number,Subject,Deal_Name,Customer_No,Purchase_Order,Quote_Name,Due_Date,Pending,Contact_Name,Carrier,Excise_Duty,Sales_Commission,Status,Account_Name,Owner,Created_By,Modified_By,Created_Time,Modified_Time,Sub_Total,Tax,Adjustment,Grand_Total,Billing_Street,Shipping_Street,Billing_City,Shipping_City,Billing_State,Shipping_State,Billing_Code,Shipping_Code,Billing_Country,Shipping_Country,Terms_and_Conditions,Description,Discount,Currency,Exchange_Rate,Layout,Tag,User_Modified_Time,System_Related_Activity_Time,User_Related_Activity_Time,System_Modified_Time,Apple_Serial_Number,Is_DEP_Order,Dep_Response,$converted
        $path = storage_path("app/app/zoho/{$id}.csv");
        $fh = fopen($path, 'r');
        $headers = fgetcsv($fh);
        while($row = fgetcsv($fh)) {

        }
    }
}
