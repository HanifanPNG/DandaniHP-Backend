<?php

namespace App\Http\Requests\Pelanggan;

use Illuminate\Foundation\Http\FormRequest;

// Validasi input penolakan penawaran.
// Alasan tidak wajib, tapi kalau diisi dibatasi 500 karakter
// supaya muat di kolom text dan tetap enak dibaca mitra.
class TolakPenawaranRequest extends FormRequest
{
    // Kepemilikan penawaran dicek lewat Policy di controller.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alasan_penolakan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
