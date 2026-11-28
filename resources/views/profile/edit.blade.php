<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">الملف الشخصي</h2>
    </x-slot>

    <div class="py-12 profile-page">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if($user->hasRole('citizen'))
                <section class="citizen-card reliability-card">
                    <div class="reliability-summary"><div><span class="eyebrow">مساهماتك في سلامة المجتمع</span><h2>{{ number_format($user->reliability_points) }} نقطة موثوقية</h2><p>تُضاف النقاط بعد اعتماد البلاغ فقط. البلاغ المرفوض لا يخصم نقاطاً.</p></div><div class="reliability-count"><strong>{{ $approvedReportCount }}</strong><small>بلاغ معتمد</small></div></div>
                    @if($earnedBadges->isNotEmpty())<div class="earned-badges"><strong>الشارات المكتسبة</strong>@foreach($earnedBadges as $badge)<span class="source-tag">{{ $badge->name }}</span>@endforeach</div>@endif
                    <div class="points-history"><strong>سجل النقاط</strong>@forelse($recentPointTransactions as $transaction)<div><span>{{ $transaction->description ?: $transaction->reason }}</span><b>+{{ $transaction->points }}</b><time>{{ $transaction->created_at->diffForHumans() }}</time></div>@empty<p>ستظهر هنا حركات النقاط عند اعتماد أول بلاغ.</p>@endforelse</div>
                </section>
            @endif
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
