<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Representasi profil pelanggan untuk API.
class PelangganResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id_pelanggan,
            'nama_lengkap'     => $this->nama_lengkap,
            'email'            => $this->email,
            'nomor_telepon'    => $this->nomor_telepon,
            'alamat'           => $this->alamat,
            'foto_profil'      => $this->foto_profil,
            'status_verifikasi'=> $this->status_verifikasi,
        ];
    }
}