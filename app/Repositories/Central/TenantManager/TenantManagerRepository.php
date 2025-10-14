<?php

namespace App\Repositories\Central\TenantManager;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenant\Credentials\CredentialsService;
use App\Services\Tenant\Zoho\ZohoService;
use Illuminate\Support\Facades\Config;

class TenantManagerRepository
{
    /** @var CredentialsService $credentialsService */
    protected $credentialsService;
    /** @var ZohoService */
    protected $zohoService;

    public function __construct(CredentialsService $credentialsService) {
        $this->credentialsService = $credentialsService;
    }

    public function createTenant($data) {
       $tenant = Tenant::create($data);
       $tenant->domains()->create(['domain'=>$data['domain']]);
       return $tenant;
    }

    public function getTenant($id) {
        return Tenant::where('id', '=', $id)->firstOrFail();
    }

    public function createUser($tenant, $data) {
        return $tenant->run(function () use ($data) {
            $user = User::create($data);
            $token = $user->createToken("api");
            return [$user, $token];
        });
    }

    public function createCredentials($tenant, $data) {
        return $tenant->run(function() use($tenant, $data) {
            return $this->credentialsService->createCredential($data);
        });
    }
}
