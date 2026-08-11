<?php

use Illuminate\Support\Facades\Route;
use Iamdevroyal\MobileJump\Controllers\MobileSessionController;

$prefix = config('mobile-jump.route_prefix', 'mobile-jump');
$middleware = config('mobile-jump.middleware', ['api']);

Route::prefix($prefix . '/api')
    ->middleware($middleware)
    ->group(function () {
        Route::post('/connect',                [MobileSessionController::class, 'connect']);
        Route::get('/status/{sessionId}',      [MobileSessionController::class, 'status']);
        Route::get('/qr/{sessionId}',          [MobileSessionController::class, 'qr']);
        Route::delete('/disconnect/{sessionId}', [MobileSessionController::class, 'disconnect']);
    });
