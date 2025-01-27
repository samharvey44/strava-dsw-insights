<?php

namespace App\Services\Strava;

enum StravaActivityDswAnalysisScoreBandEnum: string
{
    case BAND_1 = '1';
    case BAND_2 = '2';
    case BAND_3 = '3';
    case MISSING_HEARTRATE = 'missing_heartrate';
    case MISSING_POWER = 'missing_power';
    case MISSING_ANALYSIS = 'missing_analysis';
    case NOT_ENOUGH_DATA = 'not_enough_data';
}
