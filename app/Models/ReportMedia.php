<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportMedia extends Model
{
    protected $fillable = ['report_id', 'disk', 'path', 'mime_type', 'size_bytes', 'sha256', 'position'];
    protected function casts(): array { return ['size_bytes' => 'integer', 'position' => 'integer']; }
    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
}
