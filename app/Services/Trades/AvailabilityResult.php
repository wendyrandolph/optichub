<?php

namespace App\Services\Trades;

class AvailabilityResult
{
    public bool $available;
    /** @var array<int, string> */
    public array $reasons;

    /**
     * @param bool $available
     * @param array<int, string> $reasons
     */
    public function __construct(bool $available, array $reasons = [])
    {
        $this->available = $available;
        $this->reasons = $reasons;
    }

    public function addReason(string $reason): void
    {
        if (!in_array($reason, $this->reasons, true)) {
            $this->reasons[] = $reason;
        }
    }
}
