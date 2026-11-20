<?php

namespace App\Policies;

use App\Models\Generation;
use App\Models\User;

/**
 * Authorisation rules for the Generation model.
 *
 * Registered automatically by Laravel's model/policy auto-discovery.
 */
class GenerationPolicy
{
    /**
     * Admins bypass all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) {
            return true;
        }

        return null; // fall through to individual methods
    }

    /**
     * A user may view/poll a generation only if they own it.
     */
    public function view(User $user, Generation $generation): bool
    {
        return $user->id === $generation->user_id;
    }

    /**
     * A user may remix a generation only if they own it.
     */
    public function remix(User $user, Generation $generation): bool
    {
        return $user->id === $generation->user_id;
    }
}
