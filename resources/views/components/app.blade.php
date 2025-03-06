<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}{{ empty($pageTitle) ? '' : ' | ' . $pageTitle }}</title>

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="DSW Insights" />
    <link rel="manifest" href="/site.webmanifest" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          crossorigin="anonymous"
    >
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet"
          crossorigin="anonymous"
    >
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          rel="stylesheet"
          crossorigin="anonymous"
    >
    <link href="https://cdn.jsdelivr.net/npm/pikaday/css/pikaday.css"
          rel="stylesheet"
          crossorigin="anonymous"
    >

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"
        crossorigin="anonymous"
        defer
    ></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
@auth
    <nav class="navbar navbar-expand-lg bg-body-tertiary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('home') }}">
                <img src="/logo-full.png" alt="{{ config('app.name') }}" height="30" class="mb-1 me-1" />
                {{ config('app.name') }}
            </a>
            <button class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a @class(["nav-link", "active" => Route::is('home')]) {{ Route::is('home') ? 'aria-current="page"' : '' }} href="{{ route('home') }}">
                            <i class="bi bi-house"></i>
                            Home
                        </a>
                    </li>

                    <li class="nav-item">
                        <a @class(["nav-link", "active" => Route::is('gear')]) {{ Route::is('gear') ? 'aria-current="page"' : '' }} href="{{ route('gear') }}">
                            <i class="bi bi-backpack3"></i>
                            Gear
                        </a>
                    </li>
                </ul>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-2">
        {{ Breadcrumbs::render() }}
    </div>

    <hr class="w-100 m-0" />
@endauth

<div class="container-fluid">
    {{ $slot }}
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"
></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        crossorigin="anonymous"
></script>
<script src="https://cdn.jsdelivr.net/npm/pikaday/pikaday.js"
        crossorigin="anonymous"
></script>

@stack('body_scripts')
</body>
</html>
