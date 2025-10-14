<?php

namespace App\ConnectorResponseObjects;

class AccountResponseObject extends ResponseObject
{
    protected $fillable = ['account_id', 'name', 'dep_account_id'];
    protected $primary_key = 'account_id';
}
