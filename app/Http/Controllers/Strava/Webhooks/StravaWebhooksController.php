<?php

namespace App\Http\Controllers\Strava\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\DeauthoriseStravaAthleteJob;
use App\Jobs\HandleStravaActivityWebhookJob;
use App\Services\Strava\StravaWebhookAspectTypeEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StravaWebhooksController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->query('hub_mode') === 'subscribe') {
            return $this->handleSubscriptionRequest($request);
        }

        if ($request->input('updates.authorized') === 'false') {
            return $this->handleDeauthorisationRequest($request);
        }

        if ($request->input('object_type') === 'activity') {
            return $this->handleActivityRequest($request);
        }

        return response()->json();
    }

    private function handleSubscriptionRequest(Request $request): JsonResponse
    {
        abort_unless(
            $request->query('hub_verify_token') && $request->query('hub_verify_token') === config('strava.webhook_verify_token'),
            400
        );

        return response()->json([
            'hub.challenge' => $request->query('hub_challenge'),
        ]);
    }

    private function handleDeauthorisationRequest(Request $request): JsonResponse
    {
        DeauthoriseStravaAthleteJob::dispatch($request->input('owner_id'));

        return response()->json();
    }

    private function handleActivityRequest(Request $request): JsonResponse
    {
        HandleStravaActivityWebhookJob::dispatch(
            StravaWebhookAspectTypeEnum::from($request->input('aspect_type')),
            $request->input('owner_id'),
            $request->input('object_id')
        );

        return response()->json();
    }
}
