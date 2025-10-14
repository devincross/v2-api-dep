<?php

namespace App\Services\Tenant\Zoho;

use App\ConnectorResponseObjects\AccountResponseObject;
use App\ConnectorResponseObjects\OrderResponseObject;
use App\Domains\Tenant\Zoho\ZohoDomain;
use App\Services\Tenant\ConnectorInterface\ConnectorInterface;
use App\Services\Tenant\Orders\OrdersService;
use Illuminate\Support\Collection;

class ZohoService implements ConnectorInterface
{
    /** @var ZohoDomain $zohoDomain */
    protected $zohoDomain;

    public function __construct(ZohoDomain $zohoDomain) {
        $this->zohoDomain = $zohoDomain;
    }

    public function activateConnection() {
        return $this->zohoDomain->activateConnection();
    }

    public function oAuthSetup($request) {
        return $this->zohoDomain->oAuthSetup($request);
    }

    public function getOrders(string $sinceDate): Collection {
        return $this->zohoDomain->getRecentOrders($sinceDate);
    }

    public function getOrder($external_order_id): OrderResponseObject {
        return $this->zohoDomain->getOrder($external_order_id);
    }

    public function compareOrder($order, $zOrder) {
        return $this->zohoDomain->compareOrder($order, $zOrder);
    }

    public function updateOrderStatus($external_order_id, $message) {
        return $this->zohoDomain->updateOrder($external_order_id, $message);
    }

    public function syncAccounts() {
        return $this->zohoDomain->syncAccounts();
    }

    public function getAccounts(): Collection
    {
        $accounts = $this->zohoDomain->getAllAccounts();
        $col = [];
        foreach($accounts as $account) {
            $col[] = new AccountResponseObject($account);
        }
        return Collection::make($col);
    }

    public function getAccount(string $external_account_id): AccountResponseObject
    {
        return new AccountResponseObject();
    }

    public function getAllOrders(OrdersService $ordersService) {
        return $this->zohoDomain->getAllOrders($ordersService);
    }

    public function batchAllOrders() {
        return $this->zohoDomain->batchAllOrders();
    }

    public function checkBatchStatus($id) {
        return $this->zohoDomain->checkBatchStatus($id);
    }

    public function downloadBatchFile($id) {
        return $this->zohoDomain->downloadBatchFile($id);
    }
}
