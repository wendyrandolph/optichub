<?php

namespace App\Support;

use Carbon\CarbonInterface;

class TenantTime
{
  public static function format(?CarbonInterface $dt, string $tz, string $format = 'g:ia'): string
  {
    if (! $dt) return '';

    return $dt->copy()->timezone($tz)->format($format);
  }

  public static function dayLabel(?CarbonInterface $dt, string $tz): string
  {
    if (! $dt) return '';

    $c = $dt->copy()->timezone($tz);
    return $c->isToday() ? 'Today' : $c->format('M j, Y');
  }
}
