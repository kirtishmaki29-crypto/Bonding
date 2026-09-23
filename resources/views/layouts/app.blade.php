<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Bonding · Connect under the Stars' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌙</text></svg>">

    <!-- Fonts & Assets -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#030712] text-slate-100 antialiased selection:bg-cyan-500/30 selection:text-cyan-200">
    <!-- Moon + Ocean + Galaxy Background Canvas -->
    <div class="cosmic-atmosphere" aria-hidden="true">
        <!-- Twinkling Star Layers -->
        <div class="stars-layer-1"></div>
        <div class="stars-layer-2"></div>

        <!-- Ethereal Glowing Crescent Moon -->
        <div class="ethereal-moon"></div>

        <!-- Deep Ocean Waves Container -->
        <div class="ocean-waves-container">
            <svg class="ocean-wave wave-1" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M0,0 C150,90 350,-40 500,45 C650,130 900,10 1200,60 L1200,120 L0,120 Z" fill="rgba(14, 116, 144, 0.45)"></path>
            </svg>
            <svg class="ocean-wave wave-2" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M0,20 C200,110 450,10 700,80 C950,140 1050,40 1200,70 L1200,120 L0,120 Z" fill="rgba(2, 6, 23, 0.85)"></path>
            </svg>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="relative z-10 flex min-h-screen min-h-[100dvh] flex-col">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
