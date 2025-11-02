<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('layouts.head')
<body class="ps-4 pe-4 pb-4" id="app">
    @include('layouts.navigation')

    @yield('content')
</body>
<!-- Scripts -->
@yield('javascript')

</html>