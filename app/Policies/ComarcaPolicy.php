<?php

namespace App\Policies;

use App\Models\Comarca;
use App\Models\User;

class ComarcaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Comarca $comarca): bool
    {
        return $user->isAdmin();
    }

    /** Comarques with municipalities cannot be deleted (FK restrict). */
    public function delete(User $user, Comarca $comarca): bool
    {
        return $user->isAdmin() && ! $comarca->municipios()->exists();
    }
}
