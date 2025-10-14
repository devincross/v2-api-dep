<?php

namespace App\Repositories\Tenant\Netsuite;

class NetsuiteAccountRepository extends BaseNetsuiteRepository
{
    public function accounts($date) {
        $this->loadConfig();

        $this->script_id = $this->config->netsuite_account_script_id;
        $data = $this->get(
            [
                'last_modified' => trim($date),
                'script' => $this->script_id,
                'deploy' => $this->config->netsuite_deploy_id,
                'realm' => $this->config->netsuite_realm
            ]
        );

        return $data;
    }
}
