<?php

namespace App\Http\Controllers\Tenant;

use App\Exceptions\InputValidationException;
use App\Http\Controllers\Controller;
use App\Services\Central\TenantManager\TenantManagerService;
use App\Services\Tenant\Credentials\CredentialsService;
use Illuminate\Http\Request;

class CredentialsController extends Controller
{
    /** @var CredentialsService $credentialsService */
    protected $credentialsService;

    public function __construct(CredentialsService $credentialsService) {
        $this->credentialsService = $credentialsService;
    }

    public function createCredential(Request $request) {
        try {
            $resp = $this->credentialsService->createCredential($request->all());
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }
}
