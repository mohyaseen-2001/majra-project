<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = ['created_by', 'title', 'message', 'severity', 'area_name', 'source', 'is_active', 'published_at', 'expires_at'];
    protected function casts(): array { return ['is_active' => 'boolean', 'published_at' => 'datetime', 'expires_at' => 'datetime']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
