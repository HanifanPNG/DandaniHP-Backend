<?php

namespace App\Policies;

use App\Models\MitraService;
use App\Models\PesananAktif;
use App\Models\Pelanggan;
use Illuminate\Auth\Access\Response;

class PesananPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Pelanggan $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Pelanggan $user, PesananAktif $pesanan): bool
    {
        if ($user instanceof Pelanggan) {
            return $pesanan->id_pelanggan === $user->id_pelanggan;
        }
        if ($user instanceof MitraService) {
            return $pesanan->id_mitra === $user->id_mitra;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Pelanggan $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Pelanggan $user, PesananAktif $pesananAktif): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Pelanggan $user, PesananAktif $pesananAktif): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Pelanggan $user, PesananAktif $pesananAktif): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Pelanggan $user, PesananAktif $pesananAktif): bool
    {
        return false;
    }
}
