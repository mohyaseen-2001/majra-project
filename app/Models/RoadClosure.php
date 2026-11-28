<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadClosure extends Model
{
    #[Scope]
    protected function currentlyActive(Builder $query): void
    {
        $query->where('status', 'active')
            ->where(fn (Builder $date) => $date->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $date) => $date->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    #[Scope]
    protected function historical(Builder $query): void
    {
        $query->where(fn (Builder $status) => $status->where('status', 'closed')
            ->orWhere(fn (Builder $expired) => $expired->where('status', 'active')->where('ends_at', '<=', now())))
            ->where(fn (Builder $date) => $date->whereNull('starts_at')->orWhere('starts_at', '<=', now()));
    }

    protected $fillable = ['created_by', 'title', 'description', 'area_name', 'status', 'latitude', 'longitude', 'diversion', 'starts_at', 'ends_at'];
    protected function casts(): array { return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'starts_at' => 'datetime', 'ends_at' => 'datetime']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
