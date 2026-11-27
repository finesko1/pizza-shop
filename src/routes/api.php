<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(callback: static function (): void {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware(middleware: 'throttle:5,1');

    Route::post('register', [AuthController::class, 'register']);

    Route::post('refresh', [AuthController::class, 'refresh']);
});

Route::apiResource('products', ProductController::class)
    ->only(methods: ['index', 'show']);

Route::middleware('auth:api')->group(callback: static function (): void {
    Route::prefix('auth')->group(callback: static function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    Route::apiResource('products', ProductController::class)
        ->except(methods: ['index', 'show']);
});
