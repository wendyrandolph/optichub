<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAccess($user);
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $this->canAccess($user);
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user);
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $this->canAccess($user);
    }

    private function canAccess(User $user): bool
    {
        if (method_exists($user, 'isProviderAdmin') && $user->isProviderAdmin()) {
            return true;
        }

        return ! empty($user->can_manage_support);
    }
}
