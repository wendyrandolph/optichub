<?php

return [
  // The tenant record that represents YOUR provider company
  'platform_tenant_id' => env('OPTICHUB_PLATFORM_TENANT_ID', 1),

  // Default hourly rate (used when projects don't define one)
  'default_hourly_rate' => env('OPTICHUB_DEFAULT_HOURLY_RATE', 100),

  // Client portal minimum partial payment amount
  'portal_min_partial_payment' => env('OPTICHUB_PORTAL_MIN_PAYMENT', 1),
];
