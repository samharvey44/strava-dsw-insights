<?php

namespace App\Http\Controllers\Strava\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StravaWebhooksController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->query('hub_mode') === 'subscribe') {
            abort_unless(
                $request->query('hub_verify_token') && $request->query('hub_verify_token') === config('strava.webhook_verify_token'),
                400
            );

            return response()->json([
                'hub.challenge' => $request->query('hub_challenge'),
            ]);
        }

        // TODO - handle this...
        return response()->json();
    }
}
