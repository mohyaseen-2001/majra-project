<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Alert;
use App\Models\Report;
use App\Models\RoadClosure;
use App\Services\GeminiFloodAnalyzer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function map(): View
    {
        $reports = Report::query()->whereIn('status', ['approved', 'resolved'])
            ->with('media')->latest()->limit(300)->get();

        return view('citizen.map', [
            'reports' => $reports,
            'closures' => RoadClosure::query()->where('status', 'active')->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })->get(),
            'alerts' => Alert::query()->where('is_active', true)->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->latest('published_at')->limit(20)->get(),
        ]);
    }

    public function index(): View
    {
        $reports = Report::query()->whereIn('status', ['approved', 'resolved'])
            ->with(['user:id,name', 'media'])->latest()->paginate(5);

        return view('citizen.feed', compact('reports'));
    }

    public function create(): View
    {
        return view('citizen.reports.create');
    }

    public function store(StoreReportRequest $request, GeminiFloodAnalyzer $analyzer): RedirectResponse
    {
        $validated = $request->validated();
        $photo = $validated['photo'];
        $path = $photo->store('reports', 'public');

        try {
            $report = DB::transaction(function () use ($validated, $photo, $path, $request) {
                $report = Report::create([
                    'public_id' => (string) Str::uuid(),
                    'user_id' => $request->user()->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'hazard_type' => 'flood',
                    'risk_level' => 'unknown',
                    'status' => 'pending',
                    'source' => 'citizen',
                    'area_name' => $validated['area_name'],
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'expires_at' => now()->addHours(24),
                ]);

                $report->media()->create([
                    'disk' => 'public',
                    'path' => $path,
                    'mime_type' => $photo->getMimeType(),
                    'size_bytes' => $photo->getSize(),
                    'sha256' => hash_file('sha256', $photo->getRealPath()),
                    'position' => 0,
                ]);

                return $report;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        $analyzer->analyze($report);

        return redirect()->route('reports.show', $report->public_id)
            ->with('success', 'وصل بلاغك إلى فريق المراجعة. سيظهر على الخريطة بعد التحقق منه.');
    }

    public function show(Report $report): View
    {
        abort_unless(
            in_array($report->status, ['approved', 'resolved'], true)
            || $report->user_id === auth()->id()
            || auth()->user()?->hasRole('admin'),
            404,
        );

        return view('citizen.reports.show', [
            'report' => $report->load(['user:id,name', 'media', 'aiAnalyses' => fn ($query) => $query->latest()->limit(1)])
                ->loadCount(['confirmations as still_present_count' => fn ($query) => $query->where('choice', 'still_present'), 'confirmations as ended_count' => fn ($query) => $query->where('choice', 'ended')]),
            'myConfirmation' => $report->confirmations()->where('user_id', auth()->id())->value('choice'),
        ]);
    }

    public function confirm(Request $request, Report $report): RedirectResponse
    {
        abort_unless($request->user()->hasRole('citizen'), 403);
        abort_unless($report->status === 'approved' && (! $report->expires_at || $report->expires_at->isFuture()), 404);

        $validated = $request->validate(['choice' => ['required', 'in:still_present,ended']]);
        $report->confirmations()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['choice' => $validated['choice']],
        );

        if ($validated['choice'] === 'ended' && $report->confirmations()->where('choice', 'ended')->count() >= 3) {
            $report->update(['status' => 'resolved', 'resolved_at' => now()]);
        }

        return back()->with('success', 'تم تسجيل تأكيدك، شكراً للمساعدة في تحديث حالة الطريق.');
    }
}
