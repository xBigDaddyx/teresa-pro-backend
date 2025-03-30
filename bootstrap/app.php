<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            \App\Http\Middleware\ApiVersion::class,
            \App\Infrastructure\Tenancy\TenantIdentifier::class,
            \App\Infrastructure\Tenancy\TenantDatabaseSwitcher::class,
        ]);


    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof ValidationException) {
                    return ApiResponse::error('Validation failed', $e->errors(), 422);
                }

                if ($e instanceof AuthenticationException) {
                    return ApiResponse::error('Unauthenticated', null, 401);
                }

                if ($e instanceof ThrottleRequestsException) {
                    return ApiResponse::error('Too Many Attempts.', null, 429);
                }

                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return ApiResponse::error('Resource not found', null, 404);
                }

                if ($e instanceof \App\Domain\Exceptions\ApiException) {
                    return ApiResponse::error($e->getMessage(), null, $e->getCode());
                }

                return ApiResponse::error('An unexpected error occurred: ' . $e->getMessage(), null, 500);
            }

            return null; // Return null for non-API requests to let Laravel handle the exception normally
        });
    })->create();
