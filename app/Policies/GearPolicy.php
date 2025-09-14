<?php

namespace App\Policies;

use App\Models\Gear;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GearPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Gear $gear): bool
    {
        return $gear->user_id === $user->id;
    }

    public function destroy(User $user, Gear $gear): bool
    {
        return $gear->user_id === $user->id;
    }

    public function reminders(User $user, Gear $gear): bool
    {
        return $gear->user_id === $user->id;
    }
}
