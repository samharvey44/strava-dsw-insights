<?php

return [
    'client_id' => env('STRAVA_CLIENT_ID'),
    'client_secret' => env('STRAVA_CLIENT_SECRET'),
    'auth_redirect_uri' => env('STRAVA_AUTH_REDIRECT_URI'),

    'webhook_callback_uri' => env('STRAVA_WEBHOOK_CALLBACK_URI'),
    'webhook_callback_uri_suffix' => env('STRAVA_WEBHOOK_CALLBACK_URI_SUFFIX'),
    'webhook_verify_token' => env('STRAVA_WEBHOOK_VERIFY_TOKEN'),
];
