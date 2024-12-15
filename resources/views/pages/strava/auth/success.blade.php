<x-app page-title="Home">
    <div class="pt-3">
        <div class="alert alert-success text-center" role="alert">
            Your Strava account was linked successfully!

            <div class="mt-3">
                <a href="{{ route('home') }}" class="btn btn-success">
                    <i class="bi bi-arrow-left"></i>
                    Back Home
                </a>
            </div>
        </div>
    </div>
</x-app>
