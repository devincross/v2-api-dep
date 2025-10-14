<?php

namespace App\Repositories\Tenant\Netsuite;

use App\Models\Credential;
use App\Repositories\Tenant\Credentials\InternalCredentialsRepository;
use Illuminate\Support\Facades\Http;

abstract class BaseNetsuiteRepository
{
    protected $oAuthSignature;
    protected $authHeader;
    protected $oAuthNonce;
    protected $oAuthTimestamp;
    protected $oAuthSignatureMethod = 'HMAC-SHA256';
    protected $oAuthVersion = "1.0";
    protected $method = "GET";
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

    private function init($requestParams) {
        $this->oAuthNonce = md5(mt_rand());
        $this->oAuthTimestamp = time();

        $this->loadConfig();

        $oAuthParams = array(
            'oauth_consumer_key' => $this->config['netsuite_consumer_key'],
            'oauth_nonce' => $this->oAuthNonce,
            'oauth_signature_method' => $this->oAuthSignatureMethod,
            'oauth_timestamp' => $this->oAuthTimestamp,
            'oauth_token' => $this->config['netsuite_token'],
            'oauth_version' => $this->oAuthVersion,
            'realm' => $this->config['netsuite_account']
        );

        $this->oAuthSignature = $this->createOAuthSignature(
            $this->method,
            $this->config['netsuite_restlet_host'],
            $requestParams,
            $oAuthParams,
            $this->config['netsuite_consumer_secret'],
            $this->config['netsuite_token_secret']
        );
        $this->authHeader = $this->createOAuthHeader(
            $this->oAuthSignature,
            $this->oAuthVersion,
            $this->oAuthNonce,
            $this->oAuthSignatureMethod,
            $this->oAuthTimestamp,
            $this->config['netsuite_account'],
            $this->config['netsuite_consumer_key'],
            $this->config['netsuite_token']
        );
    }

    /*
         * @parm httpMethod - GET, POST, PUT, DELETE
         * @parm baseUrl - the request URL excluding any parameters
         * @parm parameters - an array with parameters (contained in url as query string or the request body) as key value pairs ex. array(deploy => 1, script => 135, last_modified => '2018-9-1 00:00:00')
         */
    public function createOAuthSignature($httpMethod, $baseUrl, $parameters, $oAuthParam, $consumerSecret, $tokenSecret) {
        $httpMethod = strtoupper($httpMethod);

        $paramString = $this->createParameterString($parameters);

        // create the base string
        $baseString = $this->oauth_get_sbs($httpMethod, $baseUrl. '?' . $paramString, $oAuthParam);

        $signatureString = rawurlencode($consumerSecret) . '&' . rawurlencode($tokenSecret);

        $oAuthSignature = base64_encode(hash_hmac('sha256', $baseString, $signatureString, true));

        return $oAuthSignature;
    }

    protected function oauth_get_sbs(
        $requestMethod,
        $requestURL,
        $request_parameters
    ): string
    {
        return $requestMethod . "&" . rawurlencode($requestURL) . "&"
            . rawurlencode("oauth_consumer_key=" . rawurlencode($request_parameters['oauth_consumer_key'])
                . "&oauth_nonce=" . rawurlencode($request_parameters['oauth_nonce'])
                . "&oauth_signature_method=" . rawurlencode($request_parameters['oauth_signature_method'])
                . "&oauth_timestamp=" . $request_parameters['oauth_timestamp']
                . "&oauth_token=" . $request_parameters['oauth_token']
                . "&oauth_version=" . $request_parameters['oauth_version']
                . "&realm=" . $request_parameters['realm']
            );
    }

    public function encode($data) {
        return str_replace(['+', '/'], ['-', '_'], base64_encode($data));
    }

    public function createParameterString($parameterArray) {
        $encodedParameters = array();
        // encode the keys and values before sorting
        foreach($parameterArray as $key => $value) {
            $encodedParameters[rawurlencode($key)] = rawurlencode($value);
        }

        // sort the parameters by key for OAuth signature
        ksort($encodedParameters);

        // create the parameter string
        $paramCount = count($encodedParameters);
        $i = 0;
        $paramString = "";
        foreach($encodedParameters as $key => $value) {
            $i++;
            $paramString .= $key . '=' . $value;
            if ($i < $paramCount) {
                $paramString .= '&';
            }
        }

        return $paramString;
    }

    public function createOAuthHeader($oAuthSignature, $oAuthVersion, $oAuthNonce, $oAuthSignatureMethod, $oAuthTimestamp, $realm, $oAuthConsumerKey, $oAuthToken){
        $authHeader = "OAuth "
            . 'oauth_signature="' . urlencode($oAuthSignature) . '", '
            . 'oauth_version="' . ($oAuthVersion) . '", '
            . 'oauth_nonce="' . ($oAuthNonce) . '", '
            . 'oauth_signature_method="' . ($oAuthSignatureMethod) . '", '
            . 'oauth_consumer_key="' . ($oAuthConsumerKey) . '", '
            . 'oauth_token="' . ($oAuthToken) . '", '
            . 'oauth_timestamp="' . ($oAuthTimestamp) . '", '
            . 'realm="' . ($realm) . '"';

        return $authHeader;
    }

    protected function post($requestParams, $requestBody) {
        $this->method = 'POST';
        $this->init($requestParams);

        $urlParamString = $this->createParameterString($requestParams);
        $fullUrl = $this->config['netsuite_restlet_host'] . '?' . $urlParamString;
        $payload = json_encode($requestBody);

        return Http::withHeaders([
            'Authorization' => $this->authHeader,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Content-Length' => strlen($payload)
        ])->post($fullUrl, $payload)->json();
    }

    protected function get($requestParams) {
        $this->method = "GET";
        $this->init($requestParams);

        $urlParamString = $this->createParameterString($requestParams);
        $fullUrl = $this->config['netsuite_restlet_host'] . '?' . $urlParamString;
        return Http::withHeaders([
            'Authorization: ' . $this->authHeader,
            'Content-Type: application/json',
            'Accept: application/json'
        ])->get($fullUrl);

    }

    protected function put($requestParams, $requestBody) {
        $this->method = "PUT";
        $this->init($requestParams);

        $urlParamString = $this->createParameterString($requestParams);
        $fullUrl = $this->config['netsuite_restlet_host'] . '?' . $urlParamString;
        $payload = json_encode($requestBody);

        return Http::withHeaders([
            'Authorization' => $this->authHeader,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Content-Length' => strlen($payload)
        ])->put($fullUrl, $payload)->json();
    }
}
