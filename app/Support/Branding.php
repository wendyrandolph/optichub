<?php

namespace App\Support;

use App\Models\Tenant;

class Branding
{
    public static function portalTheme(?Tenant $tenant): array
    {
        $defaults = [
            'brand_name' => config('app.name', 'Renlo'),
            'primary' => '#1C2E70',
            'primary_hover' => '#172554',
            'accent' => '#8FAF9A',
            'logo_light' => asset('images/renlo.svg'),
            'logo_dark' => asset('images/renlo-white-logo.svg'),
        ];

        if (! $tenant) {
            return $defaults;
        }

        $logo = $tenant->logo_path ? asset('storage/' . ltrim($tenant->logo_path, '/')) : null;

        return [
            'brand_name' => $tenant->name ?: $defaults['brand_name'],
            'primary' => $tenant->primary_color ?: $defaults['primary'],
            'primary_hover' => $tenant->secondary_color ?: $defaults['primary_hover'],
            'accent' => $tenant->accent_color ?: $defaults['accent'],
            'logo_light' => $logo ?: $defaults['logo_light'],
            'logo_dark' => $logo ?: $defaults['logo_dark'],
        ];
    }
}
