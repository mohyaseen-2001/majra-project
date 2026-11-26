<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'ai_checked', 'approved', 'rejected', 'expired', 'resolved'];

    protected $fillable = [
        'public_id', 'user_id', 'reviewed_by', 'title', 'description', 'hazard_type', 'risk_level',
        'status', 'source', 'area_name', 'latitude', 'longitude', 'ai_checked_at', 'reviewed_at',
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
}
