<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can remove another team member's account.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isOwner() && $user->isNot($model);
    }
}
