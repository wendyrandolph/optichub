<?php

namespace App\Policies;

use App\Models\TradeQuote;
use App\Models\User;

class TradeQuotePolicy
{
    public function before(User $user, $ability)
    {
        return in_array(strtolower((string) $user->role), ['super_admin', 'superadmin', 'provider'], true) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return !empty($user->tenant_id);
    }

    public function view(User $user, TradeQuote $quote): bool
    {
        return (int) $user->tenant_id === (int) $quote->tenant_id;
    }

    public function create(User $user): bool
    {
        return !empty($user->tenant_id);
    }

    public function update(User $user, TradeQuote $quote): bool
    {
        return (int) $user->tenant_id === (int) $quote->tenant_id;
    }

    public function delete(User $user, TradeQuote $quote): bool
    {
        return (int) $user->tenant_id === (int) $quote->tenant_id;
    }
}
