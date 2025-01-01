<?php

namespace App\Services\Strava;

enum StravaWebhookAspectTypeEnum: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
}
