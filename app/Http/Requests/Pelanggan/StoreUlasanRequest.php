<?php

namespace App\Http\Requests\Pelanggan;

use Illuminate\Foundation\Http\FormRequest;

// Validasi pengiriman ulasan untuk satu pesanan.
// Rating 1-5 wajib, komentar opsional maks 1000 karakter.
// id_pelanggan & id_mitra tidak boleh dari body: diisi dari pesanan.
class StoreUlasanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating_bintang'   => ['required', 'integer', 'min:1', 'max:5'],
            'komentar_ulasan'  => ['nullable', 'string', 'max:1000'],
            'foto_ulasan'      => ['nullable', 'image', 'max:2048'], // 2MB maks

            'id_pelanggan'     => ['prohibited'],
            'id_mitra'         => ['prohibited'],
            'id_pesanan'       => ['prohibited'],
            'waktu_ulasan'     => ['prohibited'],
        ];
    }
}