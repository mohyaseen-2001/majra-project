<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadClosure extends Model
{
    protected $fillable = ['created_by', 'title', 'description', 'area_name', 'status', 'latitude', 'longitude', 'diversion', 'starts_at', 'ends_at'];
    protected function casts(): array { return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'starts_at' => 'datetime', 'ends_at' => 'datetime']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
