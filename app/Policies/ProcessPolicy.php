<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcessPolicy
{
    use HandlesAuthorization;

    /**
     * @param int  $process
     * @return bool
     */
    public function view(User $user, $process = 0)
    {
        if (empty($process)) {
            return true;
        }

        // Menus raiz (Configurações, Escola, etc.) também exigem ACL —
        // antes o bypass liberava process de raiz para qualquer usuário autenticado.
        return $user->processes()
            ->wherePivot('visualiza', 1)
            ->where('process', $process)
            ->exists();
    }

    /**
     * @param int  $process
     * @return bool
     */
    public function modify(User $user, $process)
    {
        if (empty($process)) {
            return true;
        }

        return $user->processes()
            ->wherePivot('cadastra', 1)
            ->where('process', $process)
            ->exists();
    }

    /**
     * @param int  $process
     * @return bool
     */
    public function remove(User $user, $process)
    {
        if (empty($process)) {
            return true;
        }

        return $user->processes()
            ->wherePivot('exclui', 1)
            ->where('process', $process)
            ->exists();
    }
}
