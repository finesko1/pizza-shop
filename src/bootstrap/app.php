<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(path: __DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(callback: static function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            redirect: static fn(Request $request) => $request->is('api/*') ? null : '/login',
        );
    })
    ->withExceptions(using: static function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            callback: static fn(Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(using: static function (TokenExpiredException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Token has expired.', 'code' => 'token_expired'], 401);
            }
        });

        $exceptions->render(using: static function (TokenInvalidException|TokenBlacklistedException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Token is invalid.', 'code' => 'token_invalid'], 401);
            }
        });

        $exceptions->render(using: static function (JWTException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Could not process token.', 'code' => 'token_error'], 401);
            }
        });
    })->create();
