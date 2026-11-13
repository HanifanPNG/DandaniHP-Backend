<?php

namespace App\Http\Requests\Mitra;

use Illuminate\Foundation\Http\FormRequest;

// Validasi penawaran dari mitra.
// id_mitra dan status tidak boleh dari body: keduanya ditentukan server.
class StorePenawaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'harga_penawaran'     => ['required', 'integer', 'min:1'],
            // estimasi_hari opsional, tapi kalau diisi harus masuk akal.
            'estimasi_hari'       => ['nullable', 'integer', 'min:1', 'max:255'],
            'estimasi_waktu'      => ['required', 'string', 'max:50'],
            'catatan_pemeriksaan' => ['nullable', 'string'],

            'id_mitra'            => ['prohibited'],
            'id_permintaan'       => ['prohibited'],
            'status_penawaran'    => ['prohibited'],
        ];
    }
}
