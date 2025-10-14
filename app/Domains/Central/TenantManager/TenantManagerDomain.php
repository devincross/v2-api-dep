<?php

namespace App\Domains\Central\TenantManager;

use App\Exceptions\InputValidationException;
use App\Repositories\Central\TenantManager\TenantManagerRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Events\TenantCreated;


class TenantManagerDomain
{
    /** @var TenantManagerRepository $tenantManagerRepository */
    protected $tenantManagerRepository;

    public function __construct(TenantManagerRepository $tenantManagerRepository) {
        $this->tenantManagerRepository = $tenantManagerRepository;
    }

    public function createTenant($request) {
        $this->validateTenantCreate($request);
        $data = [
            'id' => $request['subdomain'],
            'name' => $request['name'],
            'email' => $request['email'],
            'domain' => $request['domain'],
            'integration' => $request['integration'],
//            'data' => [
//                'tenancy_db_name' => $request['subdomain'],
//                'tenancy_db_username' => Str::random(16),
//                'tenancy_db_password' => Str::random(16),
//            ],
            'client_id' => Str::uuid()
        ];

        $tenant = $this->tenantManagerRepository->createTenant($data);

        //setup storage directory
        $path = storage_path("tenant{$data['id']}");
        Storage::makeDirectory($path, $mode = 0777, true, true);

        $password = Str::random(16);
        $userData = ['name'=>'api', 'email'=>'devincross@gmail.com', 'password'=>Hash::make($password)];
        list($user, $token) = $this->tenantManagerRepository->createUser($tenant, $userData);

        return ['tenant'=>$tenant, 'user'=>$user, 'api_token'=>$token->plainTextToken, 'password'=>$password];
    }

    protected function validateTenantCreate($request) {
        $validator = Validator::make($request, [
            'email' => [
                'required',
                'email',
                Rule::unique('tenants', 'email'),
                'max:255'
            ],
            'name' => 'required',
            'subdomain' => 'required|unique:domains,domain'
        ]);
        if ($validator->fails()) {
            throw new InputValidationException(json_encode($validator->errors()), "Missing data");
        }
    }

    public function createCredential($request) {
        $this->validateCredentialCreate($request);

        $tenant = $this->tenantManagerRepository->getTenant($request['tenant_id']);

        return $this->tenantManagerRepository->createCredentials($tenant, $request);
    }

    protected function validateCredentialCreate($request) {
        $validator = Validator::make($request, [
            'tenant_id' => 'required',
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            throw new InputValidationException(json_encode($validator->errors()), "Missing data");
        }
    }

    public function getTenant($tenant_id) {
        return $this->tenantManagerRepository->getTenant($tenant_id);
    }
}
