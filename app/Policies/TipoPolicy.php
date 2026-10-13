<?php

namespace App\Policies;

use App\Models\Tipo;
use App\Models\User;

class TipoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Tipo $tipo): bool
    {
        return $user->isAdmin();
    }

    /** Deleting a type only detaches it from its festivals. */
    public function delete(User $user, Tipo $tipo): bool
    {
        return $user->isAdmin();
    }
}
