<?php

namespace App\Repositories\Tenant\Credentials;

use App\Models\Credential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;

class CredentialsRepository
{
    public function createCredential($data) {
        //disable previous of this type
        Credential::where('type', '=', $data['type'])->update(['status'=>Credential::STATUS_DISABLED]);
        //dep save cert/key to files
        if($data['type'] == Credential::TYPE_DEP) {
            if(!Storage::exists("/apple")) {
                Storage::makeDirectory("/apple", 0777, true, true);
            }
            $key = "/apple/".tenant('id')."-key-".date("Y-m-d")."-".Str::uuid().".pem";
            Storage::put($key, base64_decode($data['connection_data']['ssl_key']), 'private');
            $data['connection_data']['ssl_key'] = $key;

            $cert = "/apple/".tenant('id')."-cert-".date("Y-m-d")."-".Str::uuid().".pem";
            Storage::put($cert, base64_decode($data['connection_data']['ssl_cert']), 'private');
            $data['connection_data']['ssl_cert'] = $cert;
        }
        return Credential::create($data);
    }

    public function updateCredential($id, $data) {
        return Credential::where('id', '=', $id)->update($data);
    }

    public function getActiveCredentialByType($type) {
        //check if table exists first
        if(Schema::hasTable('credentials')) {
            return Credential::
            where('type', '=', $type)
                ->where('status', '=', Credential::STATUS_ACTIVE)
                ->firstOrFail();
        }
        throw new TenancyNotInitializedException("Credentials not setup");
    }
}
