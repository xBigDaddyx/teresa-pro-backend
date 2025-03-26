<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tambahkan middleware ke grup api
        $middleware->api(append: [
            \App\Http\Middleware\ApiVersion::class,
            \App\Infrastructure\Tenancy\TenantIdentifier::class,
            \App\Infrastructure\Tenancy\TenantDatabaseSwitcher::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return ApiResponse::error('Resource not found', null, 404);
                }

                return $e instanceof \App\Domain\Exceptions\ApiException
                    ? ApiResponse::error($e->getMessage(), null, $e->getCode())
                    : ApiResponse::error('An unexpected error occurred: ' . $e->getMessage(), null, 500);
            }
        });
    })->create();
