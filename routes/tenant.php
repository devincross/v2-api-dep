<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', function () {
        return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
    });
    Route::get('/setup/zohoOauth', [\App\Http\Controllers\Tenant\SetupController::class, 'oauth2callback']);
    Route::get('/setup/netsuite/callback', [\App\Http\Controllers\Tenant\SetupController::class, 'netsuitecallback']);
});

Route::group([
    'middleware'=> [
        'api',
        InitializeTenancyBySubdomain::class,
        PreventAccessFromCentralDomains::class
    ],
    'prefix' => 'manage'
], function ($router) {
    $router->get('/zoho/activate', [\App\Http\Controllers\Tenant\SetupController::class, 'activateZoho']);
    $router->get('/netsuite/initiate', [\App\Http\Controllers\Tenant\SetupController::class, 'initiateNetsuite']);
    $router->get('/orders', [\App\Http\Controllers\Tenant\ConnectorController::class, 'getRecentOrders']);
    $router->get('/orders/{order_id}/dep', [\App\Http\Controllers\Tenant\OrdersController::class, 'depOrder']);
    $router->get('/orders/all/{page}/{count?}', [\App\Http\Controllers\Tenant\OrdersController::class, 'listOrders']);
    $router->get('/orders/external/{order_id}', [\App\Http\Controllers\Tenant\ConnectorController::class, 'getOrder']);
    $router->get('/orders/all', [\App\Http\Controllers\Tenant\ConnectorController::class, 'getAllOrders']);
    $router->get('/accounts/sync', [\App\Http\Controllers\Tenant\ConnectorController::class, 'syncAccounts']);
    $router->get('/orders/sync', [\App\Http\Controllers\Tenant\ConnectorController::class, 'syncOrders']);
    $router->get('/orders/sync/{order_id}', [\App\Http\Controllers\Tenant\ConnectorController::class, 'syncOrderWithSource']);
    $router->get('/orders/sync/external/{external_order_id}', [\App\Http\Controllers\Tenant\ConnectorController::class, 'syncOrderWithSourceExternalId']);
    $router->get('/orders/{order_id}/logs', [\App\Http\Controllers\Tenant\OrdersController::class, 'getOrderLogs']);
    $router->get('/orders/{order_id}/enroll', [\App\Http\Controllers\Tenant\OrdersController::class, 'manualEnroll']);
    $router->get('/orders/{order_id}/override', [\App\Http\Controllers\Tenant\OrdersController::class, 'manualOverride']);
    $router->get('/orders/{order_id}/void', [\App\Http\Controllers\Tenant\OrdersController::class, 'manualVoid']);
    $router->get('/orders/{order_id}/return', [\App\Http\Controllers\Tenant\OrdersController::class, 'manualReturn']);
    $router->get('/orders/dep-audit', [\App\Http\Controllers\Tenant\OrdersController::class, 'depStatusAudit']);
    $router->get('/dep-status', [\App\Http\Controllers\Tenant\OrdersController::class, 'rescheduleDepStatus']);
    $router->post('/orders', [\App\Http\Controllers\Tenant\ConnectorController::class, 'importOrder']);
    $router->get('/batch-orders', [\App\Http\Controllers\Tenant\ConnectorController::class, 'batchOrders']);
    $router->get('/batch-orders/status/{id}', [\App\Http\Controllers\Tenant\ConnectorController::class, 'batchStatusOrders']);
    $router->get('/batch-orders/download/{id}', [\App\Http\Controllers\Tenant\ConnectorController::class, 'downloadBatchFile']);
});

Route::group([
    'middleware'=> [
        'api',
        InitializeTenancyBySubdomain::class,
        PreventAccessFromCentralDomains::class,
    ],
    'prefix' => 'auth',
], function ($router) {
    $router->post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
    $router->post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
    $router->post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
    $router->post('/refresh', [\App\Http\Controllers\AuthController::class, 'refresh']);
    $router->get('/users', [\App\Http\Controllers\AuthController::class, 'getAllUsers']);
    Route::delete('/{id}', [\App\Http\Controllers\AuthController::class, 'delete']);
    $router->patch('/{id}', [\App\Http\Controllers\AuthController::class, 'update']);
    $router->get('/{id}', [\App\Http\Controllers\AuthController::class, 'get']);

});
