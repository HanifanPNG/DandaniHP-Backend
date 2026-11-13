<?php

namespace App\Policies;

use App\Models\Pelanggan;
use App\Models\PermintaanService;
use Illuminate\Auth\Access\Response;

class PermintaanPolicy
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
    public function view(Pelanggan $user, PermintaanService $permintaanService): bool
    {
        return $user->id_pelanggan === $permintaanService->id_pelanggan;
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
    public function update(Pelanggan $user, PermintaanService $permintaanService): bool
    {
        return $user->id_pelanggan === $permintaanService->id_pelanggan;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Pelanggan $user, PermintaanService $permintaanService): bool
    {
        return $user->id_pelanggan === $permintaanService->id_pelanggan;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Pelanggan $user, PermintaanService $permintaanService): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Pelanggan $user, PermintaanService $permintaanService): bool
    {
        return false;
    }
}
