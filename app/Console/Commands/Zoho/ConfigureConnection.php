<?php

namespace App\Console\Commands\Zoho;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ConfigureConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoho:connect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $client_id = config('zoho.client_id');
        $url = str_ireplace("{ID}", tenant('id'), env("TENANT_API_URL"));
        $client_domain = $url . '/setup/zohoOauth';
        $scope = config('zoho.oauth_scope');
        $prompt = 'consent';
        $response_type = 'code';
        $access_type = config('zoho.access_type');

        $redirect_url = "https://accounts.zoho.com/oauth/v2/auth?scope={$scope}&prompt={$prompt}&client_id={$client_id}&response_type={$response_type}&access_type={$access_type}&redirect_uri={$client_domain}";

        //setup needed storage folders/files
        $path = storage_path("tenant".tenant('id'));
        Storage::makeDirectory("/zoho/oauth/logs", $mode = 0777, true, true);
        Storage::makeDirectory("/zoho/oauth/tokens", $mode = 0777, true, true);
        Storage::put("/zoho/oauth/logs/ZCRMClientLibrary.log", "");
        Storage::put("/zoho/oauth/tokens/zcrm_oauthtokens.txt", "");
        $this->info('Copy the following url, past on browser and hit return.');
        $this->line($redirect_url);
    }
}
