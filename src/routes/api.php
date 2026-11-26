<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::post('register', [AuthController::class, 'register']);

    Route::post('refresh', [AuthController::class, 'refresh']);
});

Route::apiResource('products', ProductController::class)
    ->only(['index', 'show']);

Route::middleware('auth:api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    Route::apiResource('products', ProductController::class)
        ->except(['index', 'show']);
});

