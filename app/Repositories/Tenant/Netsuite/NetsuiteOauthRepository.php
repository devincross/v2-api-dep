<?php

namespace App\Repositories\Tenant\Netsuite;

use App\Models\Token;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NetsuiteOauthRepository extends BaseNetsuiteRepository
{

    public function generateRedirect() {
        $this->loadConfig();
        $code_verifier = Str::random(128);
        $code_challenge = strtr(rtrim(base64_encode(hash('sha256', $code_verifier, true)), '='), '+/', '-_');
        $query_params = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => urlencode('https://byu.api.tenants.801saas.com/manage/netsuite/initiate'),
            'scope' => 'restlets',
            'state' => Str::random(45),
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256'
        ];

        $authorizationUrl = "https://{$this->config['netsuite_account']}.app.netsuite.com/app/login/oauth2/authorize.nl?".http_build_query($query_params);
        session(['oauth2state' => $query_params['state'], 'code_verifier'=>$code_verifier]);
        return $authorizationUrl;
    }

    public function oAuthSetup(array $data) {
        $this->loadConfig();
//        if (empty($data['state']) || ($data['state'] !== session('oauth2state'))) {
//            // Handle invalid state
//            throw new \Exception("Invalid state");
//        }

        try {
            $payload = [
                'code' => $data['code'],
                'redirect_uri' => urlencode('https://byu.api.tenants.801saas.com/manage/netsuite/initiate'),
                'grant_type' => 'authorization_code',
                'code_verifier' => session('code_verifier')
            ];
            $basic = base64_encode($this->config['client_id'].":".$this->config['client_secret']);
            $resp = Http::withHeaders(['Authorization'=> "Basic {$basic}"])
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

        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            // Handle errors
        }
    }
}
