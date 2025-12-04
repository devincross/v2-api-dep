<?php

namespace App\Repositories\Tenant\Netsuite;

use Illuminate\Support\Facades\App;

class NetsuiteAccountRepository extends BaseNetsuiteRepository
{
    public function accounts($date) {
        $this->loadConfig();

        $url = "https://{$this->config['netsuite_account']}.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script={$this->config['netsuite_account_script_id']}&deploy={$this->config['netsuite_deploy_id']}&last_modified=".urlencode(trim($date));

        $data = $this->get($url);

        //need to format it
        $map = App::make($this->config['mapping_class']);
        $resp = [];
        foreach($data as $row) {
            $resp[] = $map->getAccount($row);
        }

        return $resp;
    }
}
