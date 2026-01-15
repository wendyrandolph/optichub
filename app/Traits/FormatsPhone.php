<?php

namespace App\Traits;

/**
 * Simple phone formatting helper for display.
 * Formats US 10-digit numbers to (###) ###-####; otherwise returns the original.
 */
trait FormatsPhone
{
    protected function formatPhone(?string $value): ?string
    {
        if (empty($value)) {
            return $value;
        }
        $digits = preg_replace('/\\D+/', '', $value);
        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
        }
        // If it includes country code +1 and 10 digits, format the last 10
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return sprintf('(%s) %s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7));
        }
        return $value;
    }
}
