<?php

namespace App\Services\Tenant\ConnectorInterface;

use App\ConnectorResponseObjects\AccountResponseObject;
use App\ConnectorResponseObjects\OrderResponseObject;
use Illuminate\Support\Collection;

interface ConnectorInterface
{
    public function getOrders(string $sinceDate): Collection;
    public function getOrder(string $external_order_id): OrderResponseObject;
    public function getAccounts(): Collection;
    public function getAccount(string $external_account_id): AccountResponseObject;
    public function updateOrderStatus(string $external_order_id, string $message);
}
