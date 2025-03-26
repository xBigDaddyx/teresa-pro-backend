<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiVersion
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('ApiVersion Middleware: Processing request', [
            'path' => $request->path(),
            'accept_header' => $request->header('Accept', 'application/json'),
        ]);

        $acceptHeader = $request->header('Accept', 'application/json');
        $version = 'v1'; // Default ke v1

        // Prioritaskan versi dari header Accept
        if (preg_match('/application\/vnd\.api\.v(\d+)\+json/', $acceptHeader, $matches)) {
            $version = 'v' . $matches[1];
            Log::info('ApiVersion Middleware: Version detected from header', ['version' => $version]);
        } else {
            // Jika tidak ada di header, ambil dari URL
            $path = $request->path();
            if (preg_match('/^api\/(v\d+)/', $path, $matches)) {
                $version = $matches[1];
                Log::info('ApiVersion Middleware: Version detected from URL', ['version' => $version]);
            }
        }

        // Hanya v1 yang didukung
        $supportedVersions = ['v1'];
        if (!in_array($version, $supportedVersions)) {
            Log::info('ApiVersion Middleware: Unsupported version', ['version' => $version]);
            return ApiResponse::error('Unsupported API version', null, 400);
        }

        Log::info('ApiVersion Middleware: Version accepted', ['version' => $version]);
        $request->attributes->add(['api_version' => $version]);
        return $next($request);
    }
}
