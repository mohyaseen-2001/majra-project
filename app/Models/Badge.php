<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'icon', 'points_required'];
    protected function casts(): array { return ['points_required' => 'integer']; }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class, 'user_badges')->withPivot('earned_at')->withTimestamps(); }
}
