<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Fan Hub Plus')</title>
    @include('visitor._styles')
</head>
<body class="visitor-body">
    @include('visitor._nav')
    <main>
        @yield('content')
    </main>
    @include('visitor._footer')
</body>
</html>
