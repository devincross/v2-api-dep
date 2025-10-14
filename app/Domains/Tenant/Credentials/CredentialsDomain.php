<?php

namespace App\Domains\Tenant\Credentials;

use App\Exceptions\InputValidationException;
use App\Models\Credential;
use App\Repositories\Tenant\Credentials\CredentialsRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CredentialsDomain
{
    /** @var CredentialsRepository $credentialsRepository */
    protected $credentialsRepository;

    public function __construct(CredentialsRepository $credentialsRepository) {
        $this->credentialsRepository = $credentialsRepository;
    }

    public function createCredential($request) {
        $this->validateCredential($request);

        return $this->credentialsRepository->createCredential($request);
    }

    protected function validateCredential($request) {
        $types = [Credential::TYPE_ZOHO, Credential::TYPE_NETSUITE, Credential::TYPE_DEP, Credential::TYPE_SSL, Credential::TYPE_DATABASE];
        $status = [Credential::STATUS_ACTIVE, Credential::STATUS_DISABLED];
        $validator = Validator::make($request, [
            'type' => 'required|in:'.implode(",", $types),
            'status' => 'required|in:'.implode(",", $status),
            'connection_data' => 'required'
        ]);
        if ($validator->fails()) {
            throw new InputValidationException(json_encode($validator->errors()), "Missing data");
        }

        switch($request['type']) {
            case Credential::TYPE_ZOHO:
                $validator = Validator::make($request['connection_data'], [
                    'client_id' => 'required',
                    'client_secret' => 'required',
                    'redirect_uri' => 'required',
                    'current_user_email' => 'required|email|max:255',
                    'account_field' => 'required',
                    'is_dep_field' => 'required',
                    'serials_field' => 'required',
                    'dep_status_field' => 'required',
                    'po_field' => 'required',
                    'status' => 'required',
                    'dep_order_id' => 'required',
                    'dep_ordered_at' => 'required',
                    'dep_shipped_at' => 'required'
                ]);
                if ($validator->fails()) {
                    throw new InputValidationException(json_encode($validator->errors()), "Missing Zoho data");
                }
                break;
            case Credential::TYPE_DEP:
                $validator = Validator::make($request['connection_data'], [
                    'ssl_key' => 'required',
                    'ssl_cert' => 'required',
                    'apple_api_url' => 'required',
                    'dep_reseller_id' => 'required',
                    'sap_ship_to' => 'required',
                    'sap_sold_to' => 'required'
                ]);
                if ($validator->fails()) {
                    throw new InputValidationException(json_encode($validator->errors()), "Missing DEP data");
                }
                break;
            case Credential::TYPE_NETSUITE:
                $validator = Validator::make($request['connection_data'], [
                    'netsuite_restlet_host' => 'required',
                    //'netsuite_host' => 'required',
                    //'netsuite_script_id' => 'required',
                    'netsuite_account' => 'required',
                    'netsuite_realm' => 'required',
                    'netsuite_consumer_key' => 'required',
                    'netsuite_consumer_secret' => 'required',
                    'netsuite_token' => 'required',
                    'netsuite_token_secret' => 'required',
                    'netsuite_signature_algorithm' => 'required',
                    'netsuite_deploy_id' => 'required'
                ]);
                if ($validator->fails()) {
                    throw new InputValidationException(json_encode($validator->errors()), "Missing Netsuite data");
                }
                break;
        }
    }

    public function getActiveCredentialByType(string $type) {
        return $this->credentialsRepository->getActiveCredentialByType($type);
    }
}
