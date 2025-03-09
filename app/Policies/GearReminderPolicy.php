<?php

namespace App\Policies;

use App\Models\GearReminder;
use App\Models\User;

class GearReminderPolicy
{
    public function update(User $user, GearReminder $gearReminder): bool
    {
        return $user->id === $gearReminder->gear->user_id;
    }

    public function destroy(User $user, GearReminder $gearReminder): bool
    {
        return $user->id === $gearReminder->gear->user_id;
    }
}
