<?php

namespace App\Infrastructure\Tenancy;
use Closure;
use Illuminate\Http\Request;
class TenantIdentifier
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant-ID');
        if (!$tenantId) {
            return response()->json(['error' => 'Header X-Tenant-ID diperlukan'], 400);
        }

        $tenant = TenantManager::resolve($tenantId);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant tidak ditemukan'], 403);
        }

        app()->instance('tenant', $tenant);
        return $next($request);
    }
}
