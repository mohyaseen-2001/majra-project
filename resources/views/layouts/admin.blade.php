<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'غرفة العمليات') · مَجرى</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('layouts.appearance')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body"><div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <a class="brand admin-brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark"><span></span><span></span><span></span></span><span>مَجرى<small>غرفة العمليات</small></span></a>
        <div class="sidebar-label">المراقبة والتحكم</div>
        <nav class="admin-menu">
            @php($links = [['dashboard','الرئيسية','◈'],['reports','البلاغات','⌁'],['road-closures','إغلاقات الطرق','⊘'],['alerts','التنبيهات','!'],['users','المواطنون','◎'],['companies','الشركات','▤'],['subscriptions','الاشتراكات','◇'],['payments','المدفوعات','＄'],['contacts','رسائل التواصل','✉'],['subscription-plans','الباقات','▦']])
            @foreach($links as [$key,$label,$icon])
                <a class="admin-menu-link {{ request()->routeIs($key === 'dashboard' ? 'admin.dashboard' : 'admin.section') && (request()->route('section') === $key || ($key === 'dashboard' && request()->routeIs('admin.dashboard'))) ? 'active' : '' }}" href="{{ $key === 'dashboard' ? route('admin.dashboard') : route('admin.section', ['section' => $key]) }}"><span>{{ $icon }}</span>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="sidebar-bottom"><div class="service-heartbeat"><i></i><span>نبض مَجرى<small>الاتصال مستقر</small></span></div><a href="{{ route('home') }}">عرض الموقع ↗</a></div>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar"><button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="فتح القائمة">☰</button><div><span class="topbar-kicker">منصة الإنذار المبكر الأردنية</span><strong>@yield('title', 'غرفة العمليات')</strong></div><div class="admin-account"><span>{{ auth()->user()->name }}<small>مسؤول النظام</small></span><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">خروج</button></form></div></header>
        <div class="admin-content">@yield('content')</div>
    </main>
</div>
<script>document.getElementById('sidebar-toggle')?.addEventListener('click',()=>document.getElementById('admin-sidebar').classList.toggle('open'));</script>
@yield('scripts')
</body></html>
