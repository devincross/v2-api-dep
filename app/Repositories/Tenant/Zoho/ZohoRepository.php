<?php

namespace App\Repositories\Tenant\Zoho;

use Asciisd\Zoho\Facades\ZohoManager;
use Asciisd\Zoho\ZohoModule;

class ZohoRepository
{
    /** @var ZohoModule $orders */
    protected $orders;

    protected function init() {
        $this->orders = ZohoManager::useModule('Sales_Orders');
    }

    public function getRecentOrders($date) {
        $this->init();
        return $this->orders->getRecords();
    }

    public function getOrder($external_order_id) {
        $this->init();
        return $this->orders->getRecord($external_order_id);
    }
}
