<?php

namespace App\Infrastructure\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantDatabaseSwitcher
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            $tenant = TenantManager::resolve($tenantId);
            TenantManager::setCurrent($tenant);
            if ($tenant) {
                config(['database.connections.tenant' => [
                    'driver' => 'pgsql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '5432'),
                    'database' => $tenant['database'],
                    'username' => env('DB_USERNAME', 'postgres'),
                    'password' => env('DB_PASSWORD', 'C@rtini#5'),
                ]]);
                DB::setDefaultConnection('tenant');
                DB::reconnect('tenant');
            }
        }
        return $next($request);
    }
}
