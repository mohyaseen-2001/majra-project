<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query->where(fn (Builder $visible) => $visible->where('status', 'resolved')
            ->orWhere(fn (Builder $active) => $active->where('status', 'approved')
                ->where(fn (Builder $date) => $date->whereNull('expires_at')->orWhere('expires_at', '>', now()))));
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === 'resolved'
            || ($this->status === 'approved' && (! $this->expires_at || $this->expires_at->isFuture()));
    }

    public function isAutomaticallyPublished(): bool
    {
        return $this->source === 'citizen' && $this->ai_checked_at !== null && $this->reviewed_at === null
            && in_array($this->status, ['approved', 'resolved'], true);
    }
    use HasFactory;

    public const STATUSES = ['pending', 'ai_checked', 'approved', 'rejected', 'expired', 'resolved'];

    protected $fillable = [
        'public_id', 'user_id', 'reviewed_by', 'title', 'description', 'hazard_type', 'risk_level',
        'status', 'is_false_report', 'source', 'area_name', 'latitude', 'longitude', 'ai_checked_at', 'reviewed_at',
        'expires_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'ai_checked_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
            'resolved_at' => 'datetime',
            'is_false_report' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            $report->public_id ??= (string) str()->uuid();
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function media(): HasMany { return $this->hasMany(ReportMedia::class)->orderBy('position'); }
    public function aiAnalyses(): HasMany { return $this->hasMany(AiAnalysis::class); }
    public function reliabilityTransactions(): HasMany { return $this->hasMany(ReliabilityTransaction::class); }
    public function confirmations(): HasMany { return $this->hasMany(ReportConfirmation::class); }
}
