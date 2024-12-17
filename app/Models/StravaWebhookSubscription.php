<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StravaWebhookSubscription extends Model
{
    protected $fillable = [
        'strava_subscription_id',
    ];
}
