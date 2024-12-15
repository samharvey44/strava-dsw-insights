<x-app page-title="Home">
    <div class="pt-3">
        <div class="alert alert-danger text-center" role="alert">
            We were unable to link your Strava account. Please try again later.

            <div class="mt-3">
                <a href="{{ route('home') }}" class="btn btn-danger">
                    <i class="bi bi-arrow-left"></i>
                    Back Home
                </a>
            </div>
        </div>
    </div>
</x-app>
