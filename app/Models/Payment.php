<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['subscription_id', 'company_id', 'amount', 'currency', 'status', 'provider', 'checkout_session_id', 'payment_intent_id', 'paid_at', 'metadata'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'metadata' => 'array']; }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
