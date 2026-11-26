<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyArea;
use App\Models\Report;
use App\Models\RoadClosure;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyPortalController extends Controller
{
    private function company(Request $request)
    {
        return $request->user()->company()->firstOrCreate([], ['name' => $request->user()->name, 'status' => 'active']);
    }

    public function dashboard(Request $request): View
    {
        $company = $this->company($request);
        $areas = $company->areas;
        $names = $areas->pluck('area_name');
        $reports = Report::query()->whereIn('status', ['approved', 'resolved'])->whereIn('area_name', $names)->latest()->limit(6)->get();

        return view('company.dashboard', [
            'company' => $company,
            'areaCount' => $areas->count(),
            'reports' => $reports,
            'closureCount' => RoadClosure::query()->where('status', 'active')->whereIn('area_name', $names)->count(),
            'activeSubscription' => $company->subscriptions()->with('plan')->where('status', 'active')->latest()->first(),
        ]);
    }

    public function areas(Request $request): View
    {
        return view('company.areas', ['areas' => $this->company($request)->areas()->orderBy('area_name')->get()]);
    }

    public function storeArea(Request $request): RedirectResponse
    {
        $company = $this->company($request);
        $data = $request->validate([
            'area_name' => ['required', 'string', 'max:120'],
            'center_latitude' => ['nullable', 'numeric', 'between:29,34'],
            'center_longitude' => ['nullable', 'numeric', 'between:34,40'],
            'radius_km' => ['required', 'integer', 'between:1,100'],
        ]);
        $company->areas()->updateOrCreate(['area_name' => trim($data['area_name'])], $data + ['alerts_enabled' => true]);

        return back()->with('success', 'تم حفظ منطقة المتابعة.');
    }

    public function destroyArea(Request $request, CompanyArea $area): RedirectResponse
    {
        abort_unless($area->company_id === $this->company($request)->id, 404);
        $area->delete();

        return back()->with('success', 'تم حذف المنطقة من قائمة المتابعة.');
    }

    public function reports(Request $request): View
    {
        $names = $this->company($request)->areas()->pluck('area_name');
        $query = Report::query()->whereIn('status', ['approved', 'resolved'])->whereIn('area_name', $names);
        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->string('risk_level'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }
        $reports = $query->with('media')->latest()->paginate(15)->withQueryString();
        $closures = RoadClosure::query()->whereIn('area_name', $names)->latest()->limit(30)->get();

        return view('company.reports', compact('reports', 'closures'));
    }

    public function subscription(Request $request): View
    {
        $company = $this->company($request);

        return view('company.subscription', [
            'plans' => SubscriptionPlan::query()->where('is_active', true)->orderBy('price')->get(),
            'subscription' => $company->subscriptions()->with('plan')->latest()->first(),
            'payments' => $company->payments()->latest()->limit(10)->get(),
            'stripeReady' => str_starts_with((string) config('services.stripe.secret'), 'sk_test_') && (bool) config('services.stripe.key'),
        ]);
    }
}
