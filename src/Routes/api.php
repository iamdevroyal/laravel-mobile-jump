<?php

use Illuminate\Support\Facades\Route;
use Iamdevroyal\MobileJump\Controllers\MobileSessionController;

$prefix     = config('mobile-jump.route_prefix', 'mobile-jump');
$middleware = config('mobile-jump.middleware', ['api']);

$routes = function () {
    Route::post('/connect',                  [MobileSessionController::class, 'connect']);
    Route::get('/status/{sessionId}',        [MobileSessionController::class, 'status']);
    Route::get('/qr/{sessionId}',            [MobileSessionController::class, 'qr']);
    Route::delete('/disconnect/{sessionId}', [MobileSessionController::class, 'disconnect']);
};

// 1. Configured custom prefix route (e.g. /mobile-jump/api/*)
Route::prefix($prefix . '/api')
    ->middleware($middleware)
    ->group($routes);

// 2. Standard Android companion app route (/api/v1/mobile/*)
if ($prefix !== 'api/v1/mobile' && ($prefix . '/api') !== 'api/v1/mobile') {
    Route::prefix('api/v1/mobile')
        ->middleware($middleware)
        ->group($routes);
}
