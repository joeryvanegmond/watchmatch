<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('layouts.head')

<body id="app">
    @yield('content')
</body>
<!-- Scripts -->
@yield('javascript')
</html>