<?php

namespace App\ConnectorResponseObjects;

class OrderResponseObject extends ResponseObject
{
    protected $fillable = [
        'order_id', 'account', 'po', 'is_dep', 'products', 'status',
        'dep_order_id', 'dep_ordered_at', 'dep_shipped_at', 'source'
    ];
    protected $primary_key = 'order_id';
    protected $created_at = 'external_created_at';
    protected $updated_at = 'external_updated_at';
}
