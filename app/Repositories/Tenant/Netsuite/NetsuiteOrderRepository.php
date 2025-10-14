<?php

namespace App\Repositories\Tenant\Netsuite;

class NetsuiteOrderRepository extends BaseNetsuiteRepository
{
    private $script_id = "";

    public function recentOrders($date) {
        $this->loadConfig();

        $this->script_id = $this->config['netsuite_order_script_id'];
        $data = $this->get(
            [
                'last_modified' => trim($date),
                'script' => $this->script_id,
                'deploy' => $this->config['netsuite_deploy_id'],
                'realm' => $this->config['netsuite_realm']
            ]
        );

        return $data;
    }

    public function getOrder($order_id) {
        $this->loadConfig();

        $this->script_id = $this->config['netsuite_order_script_id'];
        $data = $this->get(
            [
                'order_id' => $order_id,
                'script' => $this->script_id,
                'deploy' => $this->config['netsuite_deploy_id'],
                'realm' => $this->config['netsuite_realm']
            ]
        );

        dd($data);

        return $data;
    }

    public function update($id,$resp,$status) {
        $this->loadConfig();

        $this->script_id = $this->config['netsuite_order_script_id'];
        $data = $this->put(
            [
                'script' => $this->script_id,
                'deploy' => $this->config['netsuite_deploy_id'],
                'realm' => $this->config['netsuite_realm']
            ],
            [
                'order_id' => $id,
                'dep_response'=> $resp,
                'dep_status' => $status
            ]
        );
    }
}
