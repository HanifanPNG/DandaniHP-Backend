<?php

namespace App\Policies;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PesananAktif;

class PesananPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Pelanggan|MitraService $user): bool
    {
        return false;
    }

    /**
     * Pesanan boleh dilihat oleh pelanggan pemilik atau mitra
     * yang mengerjakannya.
     */
    public function view(Pelanggan|MitraService $user, PesananAktif $pesanan): bool
    {
        if ($user instanceof Pelanggan) {
            return $pesanan->id_pelanggan === $user->id_pelanggan;
        }

        return $pesanan->id_mitra === $user->id_mitra;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Pelanggan|MitraService $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Pelanggan|MitraService $user, PesananAktif $pesananAktif): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Pelanggan|MitraService $user, PesananAktif $pesananAktif): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Pelanggan|MitraService $user, PesananAktif $pesananAktif): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Pelanggan|MitraService $user, PesananAktif $pesananAktif): bool
    {
        return false;
    }
}
