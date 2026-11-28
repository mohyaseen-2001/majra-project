<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>الباقات · مَجرى</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">@include('layouts.appearance') @vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body><div class="majra-site"><div class="site-noise"></div>@include('layouts.public-header')
<main class="citizen-page public-page"><span class="eyebrow">مساحة عمل أوضح</span><h1>باقات متابعة للمؤسسات</h1><p class="public-lead">الدفع تجريبي وما رح ينخصم منك مبلغ حقيقي. تتفعّل الباقة تلقائيًا بعد تأكيد الدفع.</p><div class="plan-grid">@forelse($plans as $plan)<article class="citizen-card plan-card"><span class="eyebrow">{{ $plan->price == 0 ? 'ابدأ مجاناً' : 'اشتراك تجريبي' }}</span><h2>{{ $plan->name }}</h2><div class="plan-price">{{ number_format((float) $plan->price, 2) }} <small>{{ $plan->currency }} / {{ $plan->interval === 'year' ? 'سنة' : 'شهر' }}</small></div><ul>@foreach(($plan->features ?? []) as $feature)<li>{{ $feature }}</li>@endforeach</ul>@include('company.plan-details')@guest
<a class="button button-small" href="{{ route('register',['role'=>'company']) }}">إنشاء حساب شركة</a>
@else
@if(auth()->user()->hasRole('company'))
<a class="button button-small" href="{{ route('company.subscription') }}">اختيار الباقة</a>
@endif
@endguest
</article>@empty<div class="citizen-card">لا توجد باقات منشورة الآن.</div>@endforelse</div></main></div></body></html>
