<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\Report;
use App\Models\RoadClosure;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ReliabilityPointsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AdminDataController extends Controller
{
    public function __construct(private ReliabilityPointsService $points) {}

    public const RESOURCES = ['reports', 'road-closures', 'alerts', 'users', 'companies', 'subscriptions', 'payments', 'contacts', 'subscription-plans'];

    private const CONFIG = [
        'reports' => [Report::class, ['user:id,name,email', 'reviewer:id,name', 'media', 'aiAnalyses'], ['title', 'description', 'status', 'risk_level', 'area_name']],
        'road-closures' => [RoadClosure::class, ['creator:id,name'], ['title', 'area_name', 'status']],
        'alerts' => [Alert::class, ['creator:id,name'], ['title', 'area_name', 'severity', 'is_active']],
        'users' => [User::class, ['company'], ['name', 'email', 'role', 'is_suspended']],
        'companies' => [Company::class, ['user:id,name,email', 'areas', 'subscriptions'], ['name', 'status', 'business_type']],
        'subscriptions' => [Subscription::class, ['company', 'plan', 'payments'], ['status', 'company_id']],
        'payments' => [Payment::class, ['company', 'subscription'], ['status', 'company_id']],
        'contacts' => [Contact::class, ['user:id,name'], ['subject', 'email', 'status']],
        'subscription-plans' => [SubscriptionPlan::class, [], ['name', 'slug', 'is_active']],
    ];

    public function index(Request $request, string $resource): JsonResponse
    {
        $this->authorizeMajraResource($resource, 'viewAny');
        [$model, $relations, $searchable] = self::CONFIG[$resource];
        $query = $model::query()->with($relations)->latest();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function (Builder $builder) use ($search, $searchable) {
                foreach ($searchable as $column) {
                    $builder->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        foreach (['status', 'risk_level', 'severity', 'role', 'is_active'] as $filter) {
            if ($request->filled($filter) && in_array($filter, $searchable, true)) {
                $query->where($filter, $request->query($filter));
            }
        }

        $page = $query->paginate(5);

        if ($resource === 'users') {
            $page->getCollection()->each(function (User $user) {
                $user->setAttribute('company_name', $user->company?->name);
                $user->setAttribute('registration_number', $user->company?->registration_number);
                $user->setAttribute('business_type', $user->company?->business_type);
            });
        }
        if ($resource === 'companies') {
            $page->getCollection()->each(function (Company $company) {
                $company->setAttribute('account_name', $company->user?->name);
                $company->setAttribute('email', $company->user?->email);
                $company->setAttribute('subscription_status', $company->subscriptions->sortByDesc('created_at')->first()?->status ?? 'none');
            });
        }

        return response()->json([
            'data' => $page->items(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(string $resource, int $record): JsonResponse
    {
        [$model, $relations] = self::CONFIG[$resource];
        $item = $model::with($relations)->findOrFail($record);
        $this->authorizeMajraResource($resource, 'view', $item);

        return response()->json(['data' => $item]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $this->authorizeMajraResource($resource, 'create');
        $validated = $this->normalizeDates($request->validate($this->rules($resource, true)));
        [$model] = self::CONFIG[$resource];

        if (in_array($resource, ['alerts', 'road-closures'], true)) {
            $validated['created_by'] = $request->user()->id;
        }
        if ($resource === 'reports') {
            $validated['public_id'] = (string) str()->uuid();
            $validated['reviewed_by'] = $request->user()->id;
            $validated['reviewed_at'] = now();
            $validated['source'] = 'admin';
        }

        if ($resource === 'users') {
            $item = DB::transaction(fn () => $this->createUser($validated));
        } elseif ($resource === 'companies') {
            $item = DB::transaction(fn () => $this->createCompanyWithAccount($validated));
        } else {
            $item = $model::create($validated);
        }

        return response()->json(['data' => $item->refresh()], Response::HTTP_CREATED);
    }

    public function update(Request $request, string $resource, int $record): JsonResponse
    {
        [$model] = self::CONFIG[$resource];
        $item = $model::findOrFail($record);
        $this->authorizeMajraResource($resource, 'update', $item);
        $validated = $this->normalizeDates($request->validate($this->rules($resource, false)));
        $previousStatus = $resource === 'reports' ? $item->status : null;
        $previousFalseFlag = $resource === 'reports' ? (bool) $item->is_false_report : false;
        $nextStatus = $validated['status'] ?? $previousStatus;
        $nextFalseFlag = (bool) ($validated['is_false_report'] ?? $previousFalseFlag);

        if ($resource === 'reports' && $nextFalseFlag && $nextStatus !== 'rejected') {
            throw ValidationException::withMessages(['is_false_report' => 'لا يمكن تسجيل البلاغ الكاذب إلا مع قرار الرفض.']);
        }

        if ($resource === 'users' && $item->id === $request->user()->id) {
            unset($validated['role'], $validated['is_suspended']);
        }
        if ($resource === 'users' && $item->company && isset($validated['role']) && $validated['role'] !== 'company') {
            return response()->json(['message' => 'احذف ملف الشركة بعد انتهاء اشتراكها قبل تغيير دور حسابها.'], Response::HTTP_CONFLICT);
        }
        if ($resource === 'reports' && isset($validated['status']) && in_array($validated['status'], ['approved', 'rejected', 'resolved'], true)) {
            $validated['reviewed_by'] = $request->user()->id;
            $validated['reviewed_at'] = now();
            if ($validated['status'] === 'resolved') {
                $validated['resolved_at'] = now();
            }
        }

        if ($resource === 'reports' && array_key_exists('admin_note', $validated)) {
            $analysis = $item->aiAnalyses()->latest()->first();
            if ($analysis) {
                $analysis->update(['admin_notes' => $validated['admin_note']]);
            } else {
                $item->aiAnalyses()->create(['provider' => 'manual', 'status' => 'reviewed', 'admin_notes' => $validated['admin_note']]);
            }
            unset($validated['admin_note']);
        }

        if ($resource === 'users') {
            DB::transaction(function () use ($item, &$validated) {
                $companyFields = Arr::only($validated, ['company_name', 'registration_number', 'business_type', 'phone', 'website']);
                unset($validated['company_name'], $validated['registration_number'], $validated['business_type'], $validated['phone'], $validated['website']);
                $password = $validated['password'] ?? null;
                unset($validated['password']);
                $item->update($validated);
                if ($password) $item->update(['password' => $password]);
                if (($item->role === 'company' || $item->company) && $companyFields) {
                    $companyFields['name'] = $companyFields['company_name'] ?? $item->company?->name ?? $item->name;
                    unset($companyFields['company_name']);
                    $item->company()->updateOrCreate([], $companyFields + ['status' => 'pending']);
                }
            });
        } elseif ($resource === 'companies') {
            DB::transaction(function () use ($item, &$validated) {
                $userFields = Arr::only($validated, ['account_name', 'email', 'password']);
                unset($validated['account_name'], $validated['email'], $validated['password']);
                $item->update($validated);
                $account = $item->user;
                if ($account) {
                    if (isset($userFields['account_name'])) $account->name = $userFields['account_name'];
                    if (isset($userFields['email'])) $account->email = $userFields['email'];
                    if (! empty($userFields['password'])) $account->password = $userFields['password'];
                    $account->save();
                }
            });
        } else {
            $item->update($validated);
        }

        if ($resource === 'reports' && $nextFalseFlag && ! $previousFalseFlag) {
            $this->points->flagFalseReport($item);
        }
        if ($resource === 'reports' && $nextStatus === 'approved' && $previousStatus !== 'approved') {
            $this->points->awardForApprovedReport($item->refresh());
        }

        return response()->json(['data' => $item->refresh()]);
    }

    public function destroy(Request $request, string $resource, int $record): JsonResponse
    {
        [$model] = self::CONFIG[$resource];
        $item = $model::findOrFail($record);
        $this->authorizeMajraResource($resource, 'delete', $item);

        if ($resource === 'users' && ($item->id === $request->user()->id || $item->role === 'admin')) {
            return response()->json(['message' => 'لا يمكن حذف حساب الأدمن الحالي أو حساب أدمن آخر من هذه الشاشة.'], Response::HTTP_CONFLICT);
        }
        if ($resource === 'users') {
            $company = $item->company;
            if ($company && ($reason = $this->companyDeletionBlockReason($company))) {
                return response()->json(['message' => $reason], Response::HTTP_CONFLICT);
            }
            $company?->delete();
        }
        if ($resource === 'payments' && $item->status === 'paid') {
            return response()->json(['message' => 'سجل الدفع الناجح محفوظ للتدقيق ولا يمكن حذفه.'], Response::HTTP_CONFLICT);
        }
        if ($resource === 'companies' && ($reason = $this->companyDeletionBlockReason($item))) {
            return response()->json(['message' => $reason], Response::HTTP_CONFLICT);
        }
        if ($resource === 'subscription-plans' && $item->subscriptions()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف باقة مرتبطة باشتراك؛ أوقف نشرها بدلاً من ذلك.'], Response::HTTP_CONFLICT);
        }

        if ($resource === 'reports') {
            $item->load('media')->media->each(fn ($media) => Storage::disk($media->disk)->delete($media->path));
        }
        $companyAccount = $resource === 'companies' ? $item->user : null;
        $item->delete();
        if ($companyAccount && $companyAccount->exists) $companyAccount->delete();

        return response()->json(['message' => 'تم الحذف.']);
    }

    private function authorizeMajraResource(string $resource, string $ability, mixed $item = null): void
    {
        if ($resource === 'reports') {
            $this->authorize($ability, $item ?? Report::class);
        }
    }

    private function createUser(array $validated): User
    {
        $companyFields = Arr::only($validated, ['company_name', 'registration_number', 'business_type', 'phone', 'website']);
        unset($validated['company_name'], $validated['registration_number'], $validated['business_type'], $validated['phone'], $validated['website']);
        $user = User::create($validated);
        $user->forceFill(['email_verified_at' => now()])->save();

        if ($user->role === 'company') {
            $user->company()->create([
                'name' => $companyFields['company_name'] ?? $user->name,
                'registration_number' => $companyFields['registration_number'] ?? null,
                'business_type' => $companyFields['business_type'] ?? null,
                'phone' => $companyFields['phone'] ?? null,
                'website' => $companyFields['website'] ?? null,
                'status' => 'pending',
            ]);
        }

        return $user;
    }

    private function createCompanyWithAccount(array $validated): Company
    {
        $account = User::create([
            'name' => $validated['account_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'company',
        ]);
        $account->forceFill(['email_verified_at' => now()])->save();
        unset($validated['account_name'], $validated['email'], $validated['password']);

        return Company::create($validated + ['user_id' => $account->id]);
    }

    private function companyDeletionBlockReason(Company $company): ?string
    {
        if ($company->subscriptions()->whereIn('status', ['active', 'past_due', 'cancelled'])->where(function (Builder $query) {
            $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
        })->exists()) {
            return 'لا يمكن حذف الشركة قبل انتهاء اشتراكها النشط.';
        }

        if ($company->payments()->exists()) {
            return 'انتهى الاشتراك، لكن للشركة سجلات مالية محفوظة. أرشف الشركة بدل حذف سجل التدقيق.';
        }

        return null;
    }

    private function normalizeDates(array $validated): array
    {
        foreach (['starts_at', 'ends_at', 'published_at', 'expires_at'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = Carbon::parse($validated[$field])->setTimezone(config('app.timezone'));
            }
        }

        return $validated;
    }

    private function rules(string $resource, bool $creating): array
    {
        $presence = $creating ? 'required' : 'sometimes';
        $rules = match ($resource) {
            'reports' => [
                'title' => [$presence, 'string', 'max:140'], 'description' => [$presence, 'string', 'max:2000'],
                'hazard_type' => [$presence, Rule::in(['flood', 'waterlogging', 'road_hazard', 'other'])],
                'risk_level' => [$presence, Rule::in(['unknown', 'low', 'medium', 'high', 'critical'])],
                'status' => [$presence, Rule::in(Report::STATUSES)], 'area_name' => [$presence, 'string', 'max:120'],
                'latitude' => [$presence, 'numeric', 'between:29,33.5'], 'longitude' => [$presence, 'numeric', 'between:34.8,39.4'],
                'admin_note' => ['nullable', 'string', 'max:2000'],
                'is_false_report' => [$creating ? 'prohibited' : 'sometimes', 'boolean'],
            ],
            'road-closures' => [
                'title' => [$presence, 'string', 'max:140'], 'description' => ['nullable', 'string', 'max:2000'], 'area_name' => [$presence, 'string', 'max:120'],
                'status' => [$presence, Rule::in(['active', 'closed', 'demo'])], 'latitude' => [$presence, 'numeric', 'between:29,33.5'],
                'longitude' => [$presence, 'numeric', 'between:34.8,39.4'], 'diversion' => ['nullable', 'string', 'max:255'],
                'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            ],
            'alerts' => [
                'title' => [$presence, 'string', 'max:140'], 'message' => [$presence, 'string', 'max:2000'],
                'severity' => [$presence, Rule::in(['info', 'warning', 'critical'])], 'area_name' => ['nullable', 'string', 'max:120'],
                'source' => [$presence, Rule::in(['admin', 'official', 'demo'])], 'is_active' => ['sometimes', 'boolean'],
                'published_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date'],
            ],
            'users' => [
                'name' => [$presence, 'string', 'max:255'], 'email' => [$presence, 'email', 'max:255', Rule::unique('users', 'email')->ignore(request()->route('record'))],
                'role' => [$presence, Rule::in(['citizen', 'company', 'admin'])], 'is_suspended' => ['sometimes', 'boolean'],
                'password' => [$creating ? 'required' : 'sometimes', 'string', 'min:10'],
                'company_name' => ['nullable', 'required_if:role,company', 'string', 'max:255'],
                'registration_number' => ['nullable', 'string', 'max:120', Rule::unique('companies', 'registration_number')->ignore(Company::where('user_id', request()->route('record'))->value('id'))],
                'business_type' => ['nullable', 'string', 'max:80'], 'phone' => ['nullable', 'string', 'max:40'], 'website' => ['nullable', 'url', 'max:255'],
            ],
            'companies' => [
                'account_name' => [$presence, 'string', 'max:255'], 'email' => [$presence, 'email', 'max:255', Rule::unique('users', 'email')->ignore(Company::find(request()->route('record'))?->user_id)],
                'password' => [$creating ? 'required' : 'sometimes', 'nullable', 'string', 'min:10'],
                'name' => [$presence, 'string', 'max:255'], 'registration_number' => ['nullable', 'string', 'max:120', Rule::unique('companies', 'registration_number')->ignore(request()->route('record'))],
                'business_type' => ['nullable', 'string', 'max:80'], 'phone' => ['nullable', 'string', 'max:40'], 'website' => ['nullable', 'url', 'max:255'],
                'status' => [$presence, Rule::in(['pending', 'approved', 'rejected', 'archived'])],
            ],
            'subscriptions' => [
                'company_id' => [$presence, 'integer', 'exists:companies,id'], 'subscription_plan_id' => [$presence, 'integer', 'exists:subscription_plans,id'],
                'status' => [$presence, Rule::in(['pending', 'active', 'cancelled', 'expired', 'past_due'])], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date'],
            ],
            'payments' => [
                'company_id' => [$presence, 'integer', 'exists:companies,id'], 'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
                'amount' => [$presence, 'numeric', 'min:0', 'max:99999999'], 'currency' => ['sometimes', 'string', 'size:3'],
                'status' => [$presence, Rule::in(['pending', 'paid', 'failed', 'refunded'])], 'provider' => ['sometimes', Rule::in(['stripe', 'manual'])],
            ],
            'contacts' => [
                'name' => [$presence, 'string', 'max:120'], 'email' => [$presence, 'email', 'max:255'], 'subject' => [$presence, 'string', 'max:160'],
                'message' => [$presence, 'string', 'max:4000'], 'status' => [$presence, Rule::in(['new', 'read', 'replied', 'archived'])],
            ],
            'subscription-plans' => [
                'slug' => [$presence, 'string', 'max:60', Rule::unique('subscription_plans', 'slug')->ignore(request()->route('record'))],
                'name' => [$presence, 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:1000'], 'price' => [$presence, 'numeric', 'min:0', 'max:99999999'],
                'currency' => ['sometimes', 'string', 'size:3'], 'interval' => ['sometimes', Rule::in(['month', 'year'])], 'features' => ['sometimes', 'array'], 'is_active' => ['sometimes', 'boolean'],
            ],
            default => abort(404),
        };

        return $rules;
    }
}
