<?php

namespace App\Services\Tenant\Apple;

use App\Domains\Tenant\Apple\AppleDomain;

class AppleService
{
    /** @var AppleDomain $appleDomain */
    protected $appleDomain;

    public function __construct(AppleDomain $appleDomain) {
        $this->appleDomain = $appleDomain;
    }

    public function getOrder(int $order_id) {
        return $this->appleDomain->getOrder($order_id);
    }

    public function compareOrder($order, $aOrder) {
        return $this->appleDomain->compareOrder($order, $aOrder);
    }

    public function processOrder(int $order_id, $type) {
        return $this->appleDomain->processOrder($order_id, $type);
    }

    public function processResponse(int $request_id, int $order_id) {
        return $this->appleDomain->processResponse($request_id, $order_id);
    }
}
