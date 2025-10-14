<?php
return [
    'clientId'                => env('NETSUITE_CLIENT_ID'),    // The client ID assigned to you by the provider
    'clientSecret'            => env('NETSUITE_CONSUMER_SECRET'),    // The client secret assigned to you by the provider
    'redirectUri'             => config('services.google.redirect'),
    'urlAuthorize'            => 'https://accounts.google.com/o/oauth2/v2/auth',
    'urlAccessToken'          => 'https://oauth2.googleapis.com/token',
    'urlResourceOwnerDetails' => 'https://www.googleapis.com/oauth2/v3/userinfo',
];
