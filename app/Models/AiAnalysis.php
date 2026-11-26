<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    protected $fillable = [
        'report_id', 'duplicate_of_report_id', 'provider', 'status', 'is_flood', 'is_duplicate',
        'hazard_type', 'risk_level', 'confidence', 'observations', 'admin_notes', 'raw_response', 'checked_at',
    ];

    protected function casts(): array
    {
        return ['is_flood' => 'boolean', 'is_duplicate' => 'boolean', 'confidence' => 'decimal:4', 'observations' => 'array', 'checked_at' => 'datetime'];
    }

    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
    public function duplicateOfReport(): BelongsTo { return $this->belongsTo(Report::class, 'duplicate_of_report_id'); }
}
