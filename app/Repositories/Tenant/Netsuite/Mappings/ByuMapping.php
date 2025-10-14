<?php

namespace App\Repositories\Tenant\Netsuite\Mappings;

use App\ConnectorResponseObjects\OrderResponseObject;

class ByuMapping extends BaseMapping
{
    public function getOrder(array $data) {
        return new OrderResponseObject([]);
    }
}
