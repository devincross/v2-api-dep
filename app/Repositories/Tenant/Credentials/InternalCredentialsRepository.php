<?php

namespace App\Repositories\Tenant\Credentials;

use App\Models\Credential;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;

class InternalCredentialsRepository
{
    /**
     * @throws TenancyNotInitializedException
     */
    public static function getActiveCredentialByType($type) {
        //check if table exists first
        if(Schema::hasTable('credentials')) {
            return Credential::where('type', '=', $type)
                ->where('status', '=', Credential::STATUS_ACTIVE)
                ->firstOrFail();
        }
        throw new TenancyNotInitializedException("Credentials not setup");
    }
}
