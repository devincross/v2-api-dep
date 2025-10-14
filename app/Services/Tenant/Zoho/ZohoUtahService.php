<?php

namespace App\Services\Tenant\Zoho;

use App\Domains\Tenant\Zoho\ZohoDomain;
use App\Domains\Tenant\Zoho\ZohoUtahDomain;

class ZohoUtahService extends ZohoService
{
    public function __construct(ZohoUtahDomain $zohoUtahDomain) {
        $this->zohoDomain = $zohoUtahDomain;
    }

}
