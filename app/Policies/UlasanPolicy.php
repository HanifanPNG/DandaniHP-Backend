<?php

namespace App\Policies;

use App\Models\Pelanggan;
use App\Models\Ulasan;
use Illuminate\Auth\Access\Response;

class UlasanPolicy
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
    public function view(Pelanggan $user, Ulasan $ulasan): bool
    {
        return $user->id_pelanggan === $ulasan->id_pelanggan;
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
    public function update(Pelanggan $user, Ulasan $ulasan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Pelanggan $user, Ulasan $ulasan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Pelanggan $user, Ulasan $ulasan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Pelanggan $user, Ulasan $ulasan): bool
    {
        return false;
    }
}
