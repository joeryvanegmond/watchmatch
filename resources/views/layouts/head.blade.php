<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO --}}
    <title>@yield('title', 'WatchMatch – Vergelijk horloges')</title>
    <meta name="description" content="@yield('meta_description', 'Vergelijk prijzen, merken en modellen horloges op WatchMatch.')">
    <meta name="keywords" content="@yield('meta_keywords', 'horloges, vergelijken, prijzen, merken, watchmatch')">

    {{-- OpenGraph / social preview --}}
    <meta property="og:title" content="@yield('og_title', 'WatchMatch')">
    <meta property="og:description" content="@yield('og_description', 'Vind en vergelijk horloges gemakkelijk.')">
    <meta property="og:image" content="@yield('og_image', asset('images/default-watch.jpg'))">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'WatchMatch')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Vergelijk horloges eenvoudig.')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('images/default-watch.jpg'))">


    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>