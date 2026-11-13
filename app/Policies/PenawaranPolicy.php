<?php

namespace App\Policies;

use App\Models\PenawaranMitra;
use App\Models\Pelanggan;

class PenawaranPolicy
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
    public function view(Pelanggan $user, PenawaranMitra $penawaran): bool
    {
        // Penawaran tidak menyimpan id_pelanggan, jadi kepemilikan
        // dicek lewat permintaan induknya.
        return $penawaran->permintaan->id_pelanggan === $user->id_pelanggan;
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
    public function update(Pelanggan $user, PenawaranMitra $penawaran): bool
    {
        return $penawaran->permintaan->id_pelanggan === $user->id_pelanggan;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Pelanggan $user, PenawaranMitra $penawaran): bool
    {
        return $penawaran->permintaan->id_pelanggan === $user->id_pelanggan;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Pelanggan $user, PenawaranMitra $penawaranMitra): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Pelanggan $user, PenawaranMitra $penawaranMitra): bool
    {
        return false;
    }
}
