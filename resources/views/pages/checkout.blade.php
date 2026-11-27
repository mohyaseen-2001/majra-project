<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إتمام الاشتراك · مَجرى</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
<div class="majra-site">
    @include('layouts.public-header')
    <main class="citizen-page public-page">
        <span class="eyebrow">اشتراك الشركات</span>
        <h1>إتمام الاشتراك</h1>
        <p class="public-lead">تتم المدفوعات عبر Stripe Checkout في وضع الاختبار بعد تسجيل الدخول بحساب شركة.</p>
        @guest
            <a class="button" href="{{ route('register') }}">إنشاء حساب شركة</a>
        @else
            @if(auth()->user()->hasRole('company'))
                <a class="button" href="{{ route('company.subscription') }}">اختيار الباقة</a>
            @endif
        @endguest
        <a class="text-link" href="{{ route('pricing') }}">عرض الباقات</a>
    </main>
</div>
</body>
</html>
