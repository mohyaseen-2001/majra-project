<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool { return $user->hasRole('admin'); }
    public function view(User $user, Report $report): bool { return $user->hasRole('admin') || ($report->user_id === $user->id && $user->hasRole('citizen')); }
    public function create(User $user): bool { return $user->hasRole('admin', 'citizen') && ! $user->is_suspended; }
    public function update(User $user, Report $report): bool { return $user->hasRole('admin'); }
    public function delete(User $user, Report $report): bool { return $user->hasRole('admin'); }
}
