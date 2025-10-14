<?php

namespace App\Services\Tenant\Credentials;

use App\Domains\Tenant\Credentials\CredentialsDomain;

class CredentialsService
{
    /** @var CredentialsDomain $credentialsDomain */
    protected $credentialsDomain;

    public function __construct(CredentialsDomain $credentialsDomain) {
        $this->credentialsDomain = $credentialsDomain;
    }

    public function createCredential($request) {
        return $this->credentialsDomain->createCredential($request);
    }

    public function getActiveCredentialByType(string $type) {
        return $this->credentialsDomain->getActiveCredentialByType($type);
    }
}
