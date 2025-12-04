<?php

namespace App\Repositories\Tenant\Netsuite;

use Illuminate\Support\Facades\App;

class NetsuiteOrderRepository extends BaseNetsuiteRepository
{
    public function recentOrders($date) {
        $this->loadConfig();

        $url = "https://{$this->config['netsuite_account']}.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script={$this->config['netsuite_order_script_id']}&deploy={$this->config['netsuite_deploy_id']}&last_modified=".urlencode($date);
        $orders = $this->get($url);
        $resp = [];
        $map = App::make($this->config['mapping_class']);
        foreach($orders as $order) {
            $resp[] = $map->getOrder($order);
        }
        return $resp;
    }

    public function getOrder($order_id) {
        $this->loadConfig();

        $url = "https://{$this->config['netsuite_account']}.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script={$this->config['netsuite_order_script_id']}&deploy={$this->config['netsuite_deploy_id']}&order_id={$order_id}";
        $data = $this->get($url);
        //need to convert data
        $map = App::make($this->config['mapping_class']);
        return $map->getOrder($data);
    }

    public function update($id,$resp,$status) {
        $this->loadConfig();

        $url = "https://{$this->config['netsuite_account']}.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script={$this->config['netsuite_order_script_id']}&deploy={$this->config['netsuite_deploy_id']}";

        return $this->put(
            $url,
            [
                'order_id' => $id,
                'dep_response'=> $resp,
                'dep_status' => $status
            ]
        );
    }
}
