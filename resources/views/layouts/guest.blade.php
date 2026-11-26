<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'مَجرى') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <main class="auth-shell">
            <section class="auth-card">
                <div class="auth-brand">
                    <a class="brand" href="{{ route('home') }}"><span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span><span>مَجرى<small>غرفة عمليات السيول</small></span></a>
                    <a href="{{ route('home') }}">الرئيسية</a>
                </div>
                {{ $slot }}
            </section>
        </main>
    </body>
</html>
