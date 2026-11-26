<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'registration_number', 'business_type', 'phone', 'website', 'status', 'admin_notes'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function areas(): HasMany { return $this->hasMany(CompanyArea::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
