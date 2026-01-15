<?php

if (! function_exists('money')) {
  /**
   * Basic currency formatter that does NOT rely on intl/NumberFormatter.
   *
   * @param  float|int|string|null  $value
   * @param  string                 $currency  e.g. 'USD', 'EUR'
   * @param  bool                   $withSymbol
   * @return string
   */
  function money($value, string $currency = 'USD', bool $withSymbol = true): string
  {
    if ($value === null) {
      return '—';
    }

    // Normalize value to float (good enough for display from DECIMAL)
    $numeric = (float) $value;

    // Choose a symbol based on currency
    $symbol = match (strtoupper($currency)) {
      'USD' => '$',
      'CAD' => '$',
      'EUR' => '€',
      'GBP' => '£',
      default => '',
    };

    $formatted = number_format($numeric, 2, '.', ',');

    if ($withSymbol && $symbol !== '') {
      return $symbol . $formatted;
    }

    return $formatted;
  }
}
