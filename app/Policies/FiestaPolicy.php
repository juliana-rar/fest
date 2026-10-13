<?php

namespace App\Policies;

use App\Models\Fiesta;
use App\Models\User;

class FiestaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Fiesta $fiesta): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Fiesta $fiesta): bool
    {
        return $user->isAdmin();
    }

    /** Copy a festival (and its programme) to next year. */
    public function duplicate(User $user, Fiesta $fiesta): bool
    {
        return $user->isAdmin();
    }

    /** CSV import / export. */
    public function import(User $user): bool
    {
        return $user->isAdmin();
    }

    public function export(User $user): bool
    {
        return $user->isAdmin();
    }
}
