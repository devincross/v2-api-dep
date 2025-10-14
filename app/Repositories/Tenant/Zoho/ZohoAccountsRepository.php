<?php

namespace App\Repositories\Tenant\Zoho;

use Asciisd\Zoho\Facades\ZohoManager;
use Asciisd\Zoho\ZohoModule;

class ZohoAccountsRepository
{
    /** @var  ZohoModule $orders*/
    protected $accounts;

    protected function init() {
        // we can now deals with leads module
        $this->accounts = ZohoManager::useModule('Accounts');
    }

    public function getAllAcounts() {
        $this->init();
        return $this->accounts->getRecords();
    }

    public function getAccount($id) {
        $this->init();
        return $this->accounts->getRecord($id);
    }
}
