<?php

namespace App\Repositories\Tenant\Netsuite;

use App\Exceptions\NetsuiteExpiredToken;
use App\Models\Credential;
use App\Models\Token;
use App\Repositories\Tenant\Credentials\InternalCredentialsRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;

abstract class BaseNetsuiteRepository
{
    protected $access_token;
    protected $config;

    protected function loadConfig() {
        if($this->config == null) {
            $credentials = InternalCredentialsRepository::getActiveCredentialByType(Credential::TYPE_NETSUITE);

            if ($credentials == null) {
                throw new \Exception("Netsuite account missing");
            }

            $this->config = $credentials->connection_data;
        }
    }

    private function init() {
        $this->loadConfig();

        $now = Carbon::now();
        try {
            $token = Token::where('expires_at', '<', $now)->firstOrFail();
            $this->access_token = $token->access_token;
        } catch (ModelNotFoundException $ex) {
            throw new NetsuiteExpiredToken("Access token has expired");
        }
    }

    protected function post(string $url, array $data) {
        $this->init();

        return Http::withHeaders([
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json',
            'Accept: application/json'
        ])
            ->throw()
            ->post($url, $data)
            ->json();
    }

    protected function get(string $url) {
        $this->init();

        return Http::withHeaders([
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json',
            'Accept: application/json'
        ])
            ->throw()
            ->get($url)
            ->json();

    }

    protected function put(string $url, array $data) {
        $this->init();

        return Http::withHeaders([
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json',
            'Accept: application/json'
        ])
            ->throw()
            ->put($url, $data)
            ->json();
    }
}
