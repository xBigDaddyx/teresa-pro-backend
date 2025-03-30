<?php

namespace App\Infrastructure\Tenancy;

use Illuminate\Support\Facades\Cache;

class TenantManager
{
    public static $currentTenant = null;

    public static function resolve(string $subdomain): ?array
    {
        return Cache::remember("tenant_{$subdomain}", 3600, function () use ($subdomain) {
            return config('tenancy.tenants')[$subdomain] ?? null;
        });
    }

    public static function setCurrent(?array $tenant)
    {
        self::$currentTenant = $tenant;
    }

    public static function getCurrent(): ?array
    {
        return self::$currentTenant;
    }
}
