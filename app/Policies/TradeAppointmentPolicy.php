<?php

namespace App\Policies;

use App\Models\TradeAppointment;
use App\Models\User;

class TradeAppointmentPolicy
{
    public function before(User $user, $ability)
    {
        return in_array(strtolower((string) $user->role), ['super_admin', 'superadmin', 'provider'], true) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return !empty($user->tenant_id);
    }

    public function view(User $user, TradeAppointment $appointment): bool
    {
        return (int) $user->tenant_id === (int) $appointment->tenant_id;
    }

    public function create(User $user): bool
    {
        return !empty($user->tenant_id);
    }

    public function update(User $user, TradeAppointment $appointment): bool
    {
        return (int) $user->tenant_id === (int) $appointment->tenant_id;
    }

    public function delete(User $user, TradeAppointment $appointment): bool
    {
        return (int) $user->tenant_id === (int) $appointment->tenant_id;
    }
}
