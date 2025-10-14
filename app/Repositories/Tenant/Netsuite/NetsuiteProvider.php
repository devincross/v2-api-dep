<?php

namespace App\Repositories\Tenant\Netsuite;

use Illuminate\Support\Str;
use League\OAuth2\Client\Provider\GenericProvider;

class NetsuiteProvider extends GenericProvider
{
    public function setState() {
        $this->state = Str::random(45);
    }

    public function getState() {
        if($this->state == "") {
            $this->setState();
        }
        return $this->state;
    }
}
