<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('layouts.head')

<body>
    <div class="container-fluid" id="app">
        @yield('content')
    </div>
</body>
<!-- Scripts -->
@yield('javascript')
</html>