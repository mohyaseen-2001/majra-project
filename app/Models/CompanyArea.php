<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyArea extends Model
{
    protected $fillable = ['company_id', 'area_name', 'center_latitude', 'center_longitude', 'radius_km', 'alerts_enabled'];
    protected function casts(): array { return ['center_latitude' => 'decimal:7', 'center_longitude' => 'decimal:7', 'radius_km' => 'integer', 'alerts_enabled' => 'boolean']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
