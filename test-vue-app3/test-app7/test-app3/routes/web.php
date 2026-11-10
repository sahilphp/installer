<?php

declare(strict_types=1);

use App\Http\HealthController;
use App\Http\HomeController;
use App\Http\UiKitController;
use Sahil\Http\Route;

/**
 * Route table for this installation, registered through
 * App\Providers\WebRoutesProvider during boot.
 */
return [
    Route::get('/health', [HealthController::class, 'response'])->name('health'),
    Route::get('/ui-kit', [UiKitController::class, 'response'])->name('ui-kit'),
    Route::get('/', [HomeController::class, 'response'])->name('home'),
];
