<?php

namespace App\Http\Controllers\Tenant;

use App\Exceptions\InputValidationException;
use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Services\Tenant\Credentials\CredentialsService;
use App\Services\Tenant\Netsuite\NetsuiteService;
use App\Services\Tenant\Zoho\ZohoService;
use Illuminate\Http\Request;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class SetupController extends Controller
{
    public function __construct(protected CredentialsService $credentialsService, protected ZohoService $zohoService, protected NetsuiteService $netsuiteService) {}

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

    public function activateZoho(Request $request) {
        try {
            $resp = $this->zohoService->activateConnection();
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function zohocallback(Request $request)
    {
        try {
            $resp = $this->zohoService->oAuthSetup($request->all());
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function netsuitecallback(Request $request)
    {
        try {
            $resp = $this->netsuiteService->oAuthSetup($request->all());
            return response()->json(['status'=>'success', 'results'=>$resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        } catch (ZohoOAuthException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function initiateNetsuite(Request $request) {
        try {
            $resp = $this->netsuiteService->generateRedirect();
            return redirect($resp, 302);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }
}
