<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->where('status', 'active')
            ->where(fn (Builder $date) => $date->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $date) => $date->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    #[Scope]
    protected function paidPlan(Builder $query): void
    {
        $query->whereHas('plan', fn (Builder $plan) => $plan->where('price', '>', 0));
    }
    protected $fillable = ['company_id', 'subscription_plan_id', 'status', 'stripe_customer_id', 'stripe_subscription_id', 'starts_at', 'ends_at', 'cancelled_at'];
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled_at' => 'datetime']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function plan(): BelongsTo { return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
