<?php

namespace App\Http\Controllers\Central;

use App\Exceptions\InputValidationException;
use App\Http\Controllers\Controller;
use App\Services\Central\TenantManager\TenantManagerService;
use Illuminate\Http\Request;

class TenantManagerController extends Controller
{
    /** @var TenantManagerService $tenantManagerService */
    protected $tenantManagerService;

    public function __construct(TenantManagerService $tenantManagerService) {
        $this->tenantManagerService = $tenantManagerService;
       // $this->middleware('auth:api', ['except' => ['createTenant','createCredentials','getTenant']]);
    }

    public function createTenant(Request $request) {
        try {
            $resp = $this->tenantManagerService->createTenant($request->all());
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function getTenant(Request $request, $domain) {
        try {
            $tenant = $this->tenantManagerService->getTenant($domain);
            $data = ['title' => ucfirst($tenant->id), 'api' => "https://".$tenant->id.".api.tenant.801saas.com/", 'logo'=>"https://".$tenant->id.".api.tenant.801saas.com/media/logo.jpeg"];
            return response()->json(['status'=>'success', 'results'=>$data]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function createCredentials(Request $request) {
        try {
            $resp = $this->tenantManagerService->createCredential($request->all());
            return response()->json(['status'=>'success', 'results'=>$resp->toArray()]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }
}
