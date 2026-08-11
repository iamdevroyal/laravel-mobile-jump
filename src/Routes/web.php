<?php

use Illuminate\Support\Facades\Route;
use Iamdevroyal\MobileJump\Controllers\MobileDashboardController;

$prefix = config('mobile-jump.route_prefix', 'mobile-jump');

Route::prefix($prefix)
    ->middleware(['web'])
    ->group(function () {
        Route::get('/dashboard', [MobileDashboardController::class, 'index'])
            ->name('mobile-jump.dashboard');
    });
