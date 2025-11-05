<?php

namespace App\Repositories\Tenant\Netsuite;

use App\Exceptions\NetsuiteOauthExpired;
use App\Models\Token;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NetsuiteOauthRepository extends BaseNetsuiteRepository
{

    /**
     * @throws \Exception
     */
    public function generateRedirect() {
        $this->loadConfig();
        $code_verifier = Str::random(128);

        Storage::put('app/cache-verify.txt', $code_verifier);
        $state = Str::random(45);
        Storage::put('app/cache-state.txt', $state);
        $code_challenge = strtr(rtrim(base64_encode(hash('sha256', $code_verifier, true)), '='), '+/', '-_');
        $query_params = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => 'https://byu.api.tenants.801saas.com/setup/netsuite/callback',
            'scope' => 'restlets',
            'state' => $state,
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256'
        ];

        $authorizationUrl = "https://{$this->config['netsuite_account']}.app.netsuite.com/app/login/oauth2/authorize.nl?".http_build_query($query_params);
        return $authorizationUrl;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function oAuthSetup(array $data) {
        $this->loadConfig();
        $state = Storage::get('app/cache-state.txt');

        if (empty($data['state']) || ($data['state'] !== $state)) {
            // Handle invalid state
            throw new \Exception("Invalid state");
        }

        $verifier = Storage::get('app/cache-verify.txt');

        try {
            $payload = [
                'code' => $data['code'],
                'redirect_uri' => 'https://byu.api.tenants.801saas.com/setup/netsuite/callback',
                'grant_type' => 'authorization_code',
                'code_verifier' => $verifier
            ];
            $basic = base64_encode($this->config['client_id'].":".$this->config['client_secret']);
            $resp = Http::withHeaders(['Authorization'=> "Basic {$basic}", 'Content-Type'=> 'application/x-www-form-urlencoded'])
                ->post("https://{$this->config['netsuite_account']}.suitetalk.api.netsuite.com/services/rest/auth/oauth2/v1/token", $payload)
                ->throw()
                ->json();

            //setup
            $token = Token::create(
                [
                    'service' =>'netsuite',
                    'access_token' => $resp['access_token'],
                    'refresh_token' => $resp['refresh_token'],
                    'expires_at'=> Carbon::now()->addSeconds($resp['expires_in'])
                ]
            );
            return $token;

        } catch (\Exception $ex) {
            // Handle errors
            Log::error($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function refresh() {
        $this->loadConfig();
        $now = Carbon::now()->subWeek();
        try {
            //get the most recent but still account for the week before refresh fails
            $refresh = Token::where('expires_at', '<', $now)->orderBy('id', 'desc')->firstOrFail();
            $payload = [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh->refresh_token
            ];
            $basic = base64_encode($this->config['client_id'] . ":" . $this->config['client_secret']);
            $resp = Http::withHeaders(['Authorization' => "Basic {$basic}", 'Content-Type'=> 'application/x-www-form-urlencoded'])
                ->post("https://{$this->config['netsuite_account']}.suitetalk.api.netsuite.com/services/rest/auth/oauth2/v1/token", $payload)
                ->throw()
                ->json();
            $token = Token::create(
                [
                    'service' =>'netsuite',
                    'access_token' => $resp['access_token'],
                    'refresh_token' => $resp['refresh_token'],
                    'expires_at'=> Carbon::now()->addSeconds($resp['expires_in'])
                ]
            );
            return $resp['access_token'];
        } catch (ModelNotFoundException $ex) {
            //try loading the most recent and see if it will work
            throw new NetsuiteOauthExpired("Refresh token has expired");
        }
    }
}
