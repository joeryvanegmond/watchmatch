<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('layouts.head')

<body class="p-4" id="app">
    @include('layouts.navigation')

    @yield('content')
</body>
<!-- Scripts -->
@yield('javascript')
</html>