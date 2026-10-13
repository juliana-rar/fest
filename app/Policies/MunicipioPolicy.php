<?php

namespace App\Policies;

use App\Models\Municipio;
use App\Models\User;

class MunicipioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Municipio $municipio): bool
    {
        return $user->isAdmin();
    }

    /** Municipalities with festivals cannot be deleted (FK restrict). */
    public function delete(User $user, Municipio $municipio): bool
    {
        return $user->isAdmin() && ! $municipio->fiestas()->exists();
    }
}
