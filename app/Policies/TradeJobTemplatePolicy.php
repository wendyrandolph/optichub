<?php

namespace App\Policies;

use App\Models\TradeJobTemplate;
use App\Models\User;

class TradeJobTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return !$user->isTech();
    }

    public function view(User $user, TradeJobTemplate $template): bool
    {
        return !$user->isTech();
    }

    public function create(User $user): bool
    {
        return !$user->isTech();
    }

    public function update(User $user, TradeJobTemplate $template): bool
    {
        return !$user->isTech();
    }

    public function delete(User $user, TradeJobTemplate $template): bool
    {
        return !$user->isTech();
    }
}
