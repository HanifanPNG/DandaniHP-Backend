<?php

namespace App\Http\Requests\Pelanggan;

use Illuminate\Foundation\Http\FormRequest;

// Validasi update profil pelanggan.
// Hanya izinkan update pada: nama_lengkap, nomor_telepon, alamat, foto_profil.
// Status verifikasi tidak boleh diubah.
class UpdateProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_lengkap'   => ['sometimes', 'string', 'max:100'],
            'nomor_telepon'  => ['sometimes', 'string', 'max:20',
                // Unique telepon dengan pengecualian id sendiri (BR-11)
                'unique:pelanggan,nomor_telepon,' . $this->user()->id_pelanggan . ',id_pelanggan'
            ],
            'alamat'         => ['sometimes', 'string', 'max:255'],
            'foto_profil'    => ['sometimes', 'image', 'max:2048'], // 2MB maks
        ];
    }
}