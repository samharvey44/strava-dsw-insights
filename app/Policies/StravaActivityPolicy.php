<?php

namespace App\Policies;

use App\Models\Gear;
use App\Models\StravaActivity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StravaActivityPolicy
{
    use HandlesAuthorization;

    public function gear(User $user, StravaActivity $stravaActivity, ?Gear $gear = null): bool
    {
        return $user->id === $stravaActivity->rawActivity->stravaConnection->user_id
            && (is_null($gear) || $user->id === $gear->user_id);
    }
}
