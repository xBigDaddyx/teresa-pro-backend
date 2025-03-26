<?php

namespace App\Infrastructure\Tenancy;

class TenantManager
{
    public static $currentTenant = null;
    public static function resolve(string $subdomain): ?array
    {
        $tenants = config('tenancy.tenants');
        return $tenants[$subdomain] ?? null;
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
