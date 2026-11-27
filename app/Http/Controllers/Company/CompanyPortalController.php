<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Company;
use App\Models\CompanyArea;
use App\Models\Report;
use App\Models\RoadClosure;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
        $activeSubscription = $this->activeSubscription($company);
        $monitoring = (float) ($activeSubscription?->plan?->price ?? 0) > 0;
        $monitoredAreas = $monitoring ? $areas : collect();
        $reports = $this->inAreas(Report::query()->whereIn('status', ['approved', 'resolved']), $monitoredAreas)
            ->latest()->limit(6)->get();
        $alerts = Alert::query()->where('is_active', true)
            ->whereIn('area_name', $monitoredAreas->where('alerts_enabled', true)->pluck('area_name'))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest('published_at')->limit(5)->get();

        return view('company.dashboard', [
            'company' => $company,
            'areaCount' => $areas->count(),
            'reports' => $reports,
            'closureCount' => $this->inAreas(RoadClosure::query()->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now())), $monitoredAreas)->count(),
            'activeSubscription' => $activeSubscription,
            'monitoring' => $monitoring,
            'alerts' => $alerts,
        ]);
    }

    public function areas(Request $request): View|RedirectResponse
    {
        if (! $this->canMonitor($request)) {
            return $this->monitoringRequired();
        }
        return view('company.areas', ['areas' => $this->company($request)->areas()->orderBy('area_name')->get()]);
    }

    public function storeArea(Request $request): RedirectResponse
    {
        if (! $this->canMonitor($request)) {
            return $this->monitoringRequired();
        }
        $company = $this->company($request);
        $data = $request->validate([
            'area_name' => ['required', 'string', 'max:120'],
            'center_latitude' => ['nullable', 'required_with:center_longitude', 'numeric', 'between:29,34'],
            'center_longitude' => ['nullable', 'required_with:center_latitude', 'numeric', 'between:34,40'],
            'radius_km' => ['required', 'integer', 'between:1,100'],
            'alerts_enabled' => ['sometimes', 'boolean'],
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

    public function reports(Request $request): View|RedirectResponse
    {
        if (! $this->canMonitor($request)) {
            return $this->monitoringRequired();
        }
        $areas = $this->company($request)->areas;
        $filters = $request->validate([
            'risk_level' => ['nullable', 'in:low,medium,high,critical,unknown'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $query = $this->inAreas(Report::query()->whereIn('status', ['approved', 'resolved']), $areas);
        if (! empty($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        $reports = $query->with('media')->latest()->paginate(5)->withQueryString();
        $closures = $this->inAreas(RoadClosure::query(), $areas)->latest()->limit(30)->get();

        return view('company.reports', compact('reports', 'closures'));
    }

    private function activeSubscription(Company $company): ?Subscription
    {
        return $company->subscriptions()->with('plan')->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest('starts_at')->latest('id')->first();
    }

    private function canMonitor(Request $request): bool
    {
        return (float) ($this->activeSubscription($this->company($request))?->plan?->price ?? 0) > 0;
    }

    private function monitoringRequired(): RedirectResponse
    {
        return redirect()->route('company.subscription')
            ->with('info', 'اختر اشتراكاً مدفوعاً لتفعيل متابعة المناطق والتنبيهات.');
    }

    private function inAreas(Builder $query, Collection $areas): Builder
    {
        $connection = $query->getModel()->getConnection();
        $sqlite = $connection->getDriverName() === 'sqlite';
        if ($sqlite) {
            $connection->getPdo()->sqliteCreateFunction('majra_within_radius', static function (
                float $latitude, float $longitude, float $centerLatitude, float $centerLongitude, float $radius,
            ): int {
                $latitude = deg2rad($latitude);
                $dot = sin($latitude) * sin($centerLatitude)
                    + cos($latitude) * cos($centerLatitude) * cos(deg2rad($longitude) - $centerLongitude);

                return (int) ($dot >= cos($radius / 6371.0088));
            }, 5);
        }

        return $query->where(function (Builder $matches) use ($areas, $sqlite) {
            $matches->whereRaw('1 = 0');

            foreach ($areas as $area) {
                if ($area->center_latitude === null || $area->center_longitude === null) {
                    $matches->orWhere('area_name', $area->area_name);

                    continue;
                }

                $latitude = deg2rad((float) $area->center_latitude);
                $longitude = deg2rad((float) $area->center_longitude);
                if ($sqlite) {
                    $matches->orWhereRaw('majra_within_radius(latitude, longitude, ?, ?, ?) = 1', [$latitude, $longitude, $area->radius_km]);

                    continue;
                }

                $matches->orWhereRaw(
                    '(SIN(RADIANS(latitude)) * ? + COS(RADIANS(latitude)) * ? * COS(RADIANS(longitude) - ?)) >= ?',
                    [sin($latitude), cos($latitude), $longitude, cos($area->radius_km / 6371.0088)],
                );
            }
        });
    }

    public function subscription(Request $request): View
    {
        $company = $this->company($request);

        return view('company.subscription', [
            'plans' => SubscriptionPlan::query()->where('is_active', true)->orderBy('price')->get(),
            'subscription' => $company->subscriptions()->with('plan')->latest()->first(),
            'payments' => $company->payments()->latest()->limit(10)->get(),
            'stripeReady' => StripeCheckoutService::configured(),
        ]);
    }
}
