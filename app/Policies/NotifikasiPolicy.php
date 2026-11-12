<?php

namespace App\Policies;

use App\Models\Notifikasi;
use App\Models\Pelanggan;
use Illuminate\Auth\Access\Response;

class NotifikasiPolicy
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
    public function view(Pelanggan $user, Notifikasi $notifikasi): bool
    {
        return $user->id_pelanggan === $notifikasi->id_pelanggan;
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
    public function update(Pelanggan $user, Notifikasi $notifikasi): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Pelanggan $user, Notifikasi $notifikasi): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Pelanggan $user, Notifikasi $notifikasi): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Pelanggan $user, Notifikasi $notifikasi): bool
    {
        return false;
    }
}
