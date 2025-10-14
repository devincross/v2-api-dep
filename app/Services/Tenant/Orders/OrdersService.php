<?php

namespace App\Services\Tenant\Orders;

use App\Domains\Tenant\Orders\OrdersDomain;

class OrdersService
{
    /** @var OrdersDomain $ordersDomain */
    protected $ordersDomain;

    public function __construct(OrdersDomain $ordersDomain) {
        $this->ordersDomain = $ordersDomain;
    }

    public function create($data) {
        return $this->ordersDomain->create($data);
    }

    public function patch(int $order_id, $data) {
        return $this->ordersDomain->patch($order_id, $data);
    }

    public function get(int $order_id) {
        return $this->ordersDomain->get($order_id);
    }

    public function getExternalOrderId($external_id) {
        return $this->ordersDomain->getExternalOrderId($external_id);
    }

    public function syncOrders() {
        return $this->ordersDomain->syncOrders();
    }

    public function syncOrder($eOrder, $accounts, $connector) {
        return $this->ordersDomain->syncOrder($eOrder, $accounts, $connector);
    }

    public function syncOrderWithSource(int $order_id) {
        return $this->ordersDomain->syncOrderWithSource($order_id);
    }

    public function syncOrderWithSourceExternalId(string $external_order_id) {
        return $this->ordersDomain->syncOrderWithSourceExternalId($external_order_id);
    }

    public function getConnector($type = null) {
        return $this->ordersDomain->getConnector($type);
    }

    public function addProducts(int $order_id, $products) {
        return $this->ordersDomain->addProducts($order_id, $products);
    }

    public function removeProducts(int $order_id, $products) {
        return $this->ordersDomain->removeProducts($order_id, $products);
    }

    public function cleanProducts(int $order_id) {
        return $this->ordersDomain->cleanProducts($order_id);
    }

    public function patchProducts(int $order_id, $serial_number, $status) {
        return $this->ordersDomain->patchProducts($order_id, $serial_number, $status);
    }

    public function getProductWithSerial($serial) {
        return $this->ordersDomain->getProductWithSerial($serial);
    }

    public function getOrderLogs($order_id) {
        return $this->ordersDomain->getOrderLogs($order_id);
    }

    public function manualEnroll($order_id) {
        return $this->ordersDomain->manualEnroll($order_id);
    }
    public function manualOverride($order_id) {
        return $this->ordersDomain->manualOverride($order_id);
    }
    public function manualVoid($order_id) {
        return $this->ordersDomain->manualVoid($order_id);
    }
    public function manualReturn($order_id) {
        return $this->ordersDomain->manualReturn($order_id);
    }

    public function rescheduleDepStatus() {
        return $this->ordersDomain->rescheduleDepStatus();
    }

    public function getReturnCount($order_id) {
        return $this->ordersDomain->getReturnCount($order_id);
    }

    public function getOrders($page, $count) {
        return $this->ordersDomain->getOrders($page, $count);
    }

    public function getOrderCount() {
        return $this->ordersDomain->getOrderCount();
    }
}
