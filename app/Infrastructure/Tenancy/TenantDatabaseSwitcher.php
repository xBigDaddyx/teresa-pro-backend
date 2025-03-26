<?php

namespace App\Infrastructure\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantDatabaseSwitcher
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = TenantManager::getCurrent();
        if ($tenant) {
            config(['database.connections.tenant' => [
                'driver' => 'pgsql', // Ubah ke PostgreSQL
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5432'), // Port default PostgreSQL
                'database' => $tenant['database'],
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'schema' => 'public', // Skema default PostgreSQL
                'sslmode' => env('DB_SSLMODE', 'prefer'), // Opsional untuk SSL
            ]]);
            DB::setDefaultConnection('tenant');
        }

        return $next($request);
    }
}
