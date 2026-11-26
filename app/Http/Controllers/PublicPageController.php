<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Contact;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function about(): View
    {
        return view('pages.info', [
            'eyebrow' => 'عن المنصة',
            'title' => 'المعلومة المحلية تصنع فرقاً',
            'lead' => 'مَجرى منصة أكاديمية تساعد المجتمع على مشاركة مؤشرات مخاطر السيول والطرق المتأثرة في الأردن، مع مراجعة بشرية ومصدر واضح لكل معلومة.',
            'sections' => [
                ['title' => 'للمواطن', 'body' => 'يشاهد الخريطة والبلاغات المعتمدة، ويرسل صورة وموقعاً عند ملاحظة خطر. لا يتم تتبع الموقع في الخلفية.'],
                ['title' => 'للمراجعة', 'body' => 'تصل البلاغات إلى قائمة انتظار. يمكن للأدوات الآلية تقديم توصية، ويبقى اعتماد البلاغ النهائي بيد فريق المراجعة.'],
                ['title' => 'للشركات', 'body' => 'يمكن للشركات متابعة مناطق عملها والاطلاع على سجل المخاطر والإغلاقات ضمن الباقة المفعّلة.'],
            ],
        ]);
    }

    public function safety(): View { return view('pages.safety'); }

    public function pricing(): View
    {
        return view('pages.pricing', ['plans' => SubscriptionPlan::query()->where('is_active', true)->orderBy('price')->get()]);
    }

    public function contact(): View { return view('pages.contact'); }

    public function storeContact(StoreContactRequest $request): RedirectResponse
    {
        Contact::create(Arr::only($request->validated(), ['name', 'email', 'subject', 'message']) + [
            'user_id' => $request->user()?->id,
        ]);

        return redirect()->route('contact')->with('success', 'وصلت رسالتك إلى فريق مَجرى. شكراً لتواصلك.');
    }
}
