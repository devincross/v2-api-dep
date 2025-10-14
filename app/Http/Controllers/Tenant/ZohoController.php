<?php

namespace App\Http\Controllers\Tenant;

use App\Exceptions\InputValidationException;
use App\Http\Controllers\Controller;
use App\Services\Tenant\Zoho\ZohoService;
use Illuminate\Http\Request;
use zcrmsdk\oauth\exception\ZohoOAuthException;

class ZohoController extends Controller
{
    /** @var ZohoService $zohoService */
    protected $zohoService;

    public function __construct(ZohoService $zohoService)
    {
        $this->zohoService = $zohoService;
    }

    public function createCredential(Request $request)
    {
        try {
            $resp = $this->credentialsService->createCredential($request->all());
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function activateZoho(Request $request)
    {
        try {
            $resp = $this->zohoService->activateConnection();
            return response()->json(['status' => 'success', 'results' => $resp]);
        } catch (InputValidationException $ex) {
            return response()->json([
                'status' => "error",
                'message' => $ex->getErrors(),
            ]);
        }
    }

    public function oauth2callback(Request $request)
    {
        try {
            $resp = $this->zohoService->oAuthSetup($request->all());
            return response()->json(['status' => 'success', 'results' => $resp]);
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
}
