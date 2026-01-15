<?php

namespace App\Policies;

use App\Models\TradeJob;
use App\Models\User;

class TradeJobPolicy
{
    public function viewAny($user): bool
    {
        // Everyone in tenant app can at least attempt to view jobs list;
        // controller will scope results for techs.
        return true;
    }

    public function view($user, TradeJob $job): bool
    {
        if (!$user->isTech()) {
            return true;
        }

        return $job->appointments()
            ->whereHas('assignments', fn($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function create($user): bool
    {
        return !$user->isTech();
    }

    public function update($user, TradeJob $job): bool
    {
        return !$user->isTech();
    }

    public function delete($user, TradeJob $job): bool
    {
        if ($user->isTech()) {
            return false;
        }

        $role = strtolower((string) ($user->role ?? ''));
        return in_array($role, ['admin', 'super_admin', 'superadmin', 'provider'], true);
    }
}
