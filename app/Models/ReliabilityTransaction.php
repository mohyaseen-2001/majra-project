<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReliabilityTransaction extends Model
{
    protected $fillable = ['user_id', 'report_id', 'points', 'reason', 'description'];
    protected function casts(): array { return ['points' => 'integer']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
}
