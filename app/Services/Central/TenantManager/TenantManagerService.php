<?php

namespace App\Services\Central\TenantManager;

use App\Domains\Central\TenantManager\TenantManagerDomain;

class TenantManagerService
{
    /** @var TenantManagerDomain $tenantManagerDomain */
    protected $tenantManagerDomain;

    public function __construct(TenantManagerDomain $tenantManagerDomain) {
        $this->tenantManagerDomain = $tenantManagerDomain;
    }

    public function createTenant($request) {
        return $this->tenantManagerDomain->createTenant($request);
    }

    public function createCredential($request) {
        return $this->tenantManagerDomain->createCredential($request);
    }

    public function getTenant($tenant_id) {
        return $this->tenantManagerDomain->getTenant($tenant_id);
    }
}
