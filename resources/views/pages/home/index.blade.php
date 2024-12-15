<x-app page-title="Home">
    <div class="pt-3">
        @if(!auth()->user()->hasActiveStravaConnection())
            <div class="alert alert-warning text-center" role="alert">
                You have not connected your Strava account.<br/>
                Please connect your Strava account to view your insights.

                <div class="mt-3">
                    <a href="{{ $stravaAuthorisationLink }}" class="btn btn-warning" target="_blank">
                        <i class="bi bi-link"></i>
                        Connect Strava
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app>
