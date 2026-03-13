<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the authenticated user can update the target user.
     *
     * Rules:
     *   Admin   — can update anyone without restriction.
     *   Manager — can only update users with the User role (not other Managers or Admins).
     *   User    — can only update their own record.
     *
     * The exhaustive match() ensures any new role added to the enum causes a
     * compile-time error here rather than a silent security gap.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        return match($authUser->role) {
            UserRole::Admin   => true,
            UserRole::Manager => $targetUser->role === UserRole::User,
            UserRole::User    => $authUser->id === $targetUser->id,
        };
    }
}
