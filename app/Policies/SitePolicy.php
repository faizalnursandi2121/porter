<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    /**
     * FR-1: every authenticated role may browse sites — even HR, whose
     * launcher area is read-mostly.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Site $site): bool
    {
        return true;
    }

    /**
     * FR-34: site master data is Administrator-managed; Supervisi only views.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, Site $site): bool
    {
        return $user->isAdministrator();
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->isAdministrator();
    }
}
