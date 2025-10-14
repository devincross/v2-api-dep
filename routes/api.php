<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// manage tenants
Route::group(
    [
        'middleware' => [],
        'prefix' => 'tenant'
    ],
    function ($router) {
        $router->post('/create', [\App\Http\Controllers\Central\TenantManagerController::class, 'createTenant']);
        $router->get('/get/{domain}', [\App\Http\Controllers\Central\TenantManagerController::class, 'getTenant']);
        $router->post('/credentials', [\App\Http\Controllers\Central\TenantManagerController::class, 'createCredentials']);
    }
);
