<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ActivityView->value);
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->can(PermissionName::ActivityViewDetails->value);
    }

    public function export(User $user): bool
    {
        return $user->can(PermissionName::ActivityExport->value);
    }
}
