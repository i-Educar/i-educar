<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcessPolicy
{
    use HandlesAuthorization;

    /**
     * @param  int  $process
     * @return bool
     */
    public function view(User $user, $process = 0)
    {
        return $this->permite($user, $process, 'visualiza');
    }

    /**
     * @param  int  $process
     * @return bool
     */
    public function modify(User $user, $process)
    {
        return $this->permite($user, $process, 'cadastra');
    }

    /**
     * @param  int  $process
     * @return bool
     */
    public function remove(User $user, $process)
    {
        return $this->permite($user, $process, 'exclui');
    }

    private function permite(User $user, $process, string $pivot): bool
    {
        if (empty($process)) {
            return true;
        }

        $processo = (int) $process;

        if (class_exists(\iEducar\Packages\BuritiPapeisTemporarios\Services\PapeisTemporarios::class)
            && \iEducar\Packages\BuritiPapeisTemporarios\Services\PapeisTemporarios::habilitado()) {
            return \iEducar\Packages\BuritiPapeisTemporarios\Services\PapeisTemporarios::usuarioPodeProcesso($user, $processo, $pivot);
        }

        return $user->processes()
            ->wherePivot($pivot, 1)
            ->where('process', $processo)
            ->exists();
    }
}
