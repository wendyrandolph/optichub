<?php

namespace App\Policies;

use App\Models\TradeServicePlan;

class TradeServicePlanPolicy
{
    public function viewAny($user): bool
    {
        return !$user->isTech();
    }

    public function view($user, TradeServicePlan $plan): bool
    {
        return !$user->isTech();
    }

    public function create($user): bool
    {
        return !$user->isTech();
    }

    public function update($user, TradeServicePlan $plan): bool
    {
        return !$user->isTech();
    }

    public function delete($user, TradeServicePlan $plan): bool
    {
        return !$user->isTech();
    }
}
